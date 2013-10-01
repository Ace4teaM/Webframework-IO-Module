/*
    ---------------------------------------------------------------------------------------------------------------------------------------
    (C)2013 Thomas AUGUEY <contact@aceteam.org>
    ---------------------------------------------------------------------------------------------------------------------------------------
    This file is part of WebFrameWork.

    WebFrameWork is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WebFrameWork is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WebFrameWork.  If not, see <http://www.gnu.org/licenses/>.
    ---------------------------------------------------------------------------------------------------------------------------------------
*/

/**
 * @brief jQuery Upload Plugin
 * @method request
 * @memberof JQuery
 * 
 * #Introduction
 * Simplifie l'upload des fichiers
 * 
 * ## Execute un upload par packet
 * io_upload( 'packet', 'my_page_id', { request params... }, function(obj,args){ 
 *      obj;   // request object
 *      args;  // the native javascript object of response parameters
 * } )
 *
 **/
(function($)
{
    //constructeur
    $.fn.io_upload = function(p){
        var me = $(this);
        var files = me.get(0).files;
        var args = arguments;
        
        // au moins 1 fichier
        if(!files.length)
            return false;

        // SETTER
        if(typeof(p) == "string"){
            switch(p){
                /**
                 * Envoi le fichier par paquet
                 * + Permet l'upload de grands fichiers
                 * + Permet la reprise d'upload
                 */
                case "packet":
                    var callback = args[1];
                    var callback_failed = args[2];
                    
                    //urls
                    var begin_uri    = "io_begin_upload";
                    var check_uri    = "io_check_upload";
                    var packet_uri   = "io_packet_upload";
                    var io_upload_id = null;
                    var filename     = null;
                    var packet_count = packet_count;
                    var packet_size  = packet_size;
                    
                    //initialise l'upload
                    $(window).request(
                        "xarg",
                        begin_uri,
                        {
                            file_size: files[0].size,
                            content_type: files[0].type, 
                            filename: files[0].name,
                            output: 'xarg'
                        },
                        {
                            onsuccess:function(req,xargs){
                                io_upload_id = xargs.io_upload_id;
                                packet_count = xargs.packet_count;
                                packet_size  = xargs.packet_size;
                                console.log("BEGIN UPLOAD (io_upload_id="+xargs.io_upload_id+", "+req.args.file_size+" bytes)");
                            },
                            onfailed:function(req,xargs){
                                callback_failed(xargs);
                            },
                            async:false
                        }
                    );
                    if(io_upload_id == null){
                        console.log("BEGIN UPLOAD FAILED!");
                        return false;
                    }

                    // prepare la requete d'envoi
                    var packetReq = {
                        onsuccess: function (obj, args) {
                            console.log("sendAsPacket: Packet n°"+args.packet_num+" envoyé");
                            //continue l'upload avec le prochain paquet
                            $(window).request("xarg",check_uri,{io_upload_id:io_upload_id},copy(checkReq));
                        },
                        onfailed: function (obj, args) {
                            console.log("sendAsPacket: FAILED");
                            callback_failed(args);
                        },
                        onerror: function (obj) {
                            console.log("sendAsPacket: ERROR");
                        },
                        async:true
                    };

                    // prepare la requete de verification
                    var checkReq = {
                        onsuccess: function (obj, args) {
                            callback(args,filename);
                            console.log(args);
                            console.log("checkReq: FINALIZE\n");

                            //transfer les paquets
                            $(window).request(
                                "xarg",
                                "finalize_image_upload",
                                {
                                    io_upload_id: io_upload_id,
                                    output: 'xarg',
                                    token:io_upload_id,
                                    filename: '0'
                                },
                                checkReq
                            );
                                
                            console.log("checkReq: END\n");
                        },
                        onfailed: function (obj, args) {
                            //l'upload n'est pas terminé, on continue
                            if(args.error == "IO_FILE_UNCOMPLETED")
                            {
                                console.log("Continue upload with packet "+args.packet_num);
                                //lit le contenu du fichier
                                readFileOffset_base64(
                                    files[0],
                                    args.packet_offset,
                                    args.packet_size,
                                    //onLoad
                                    function (start, size, data, param) {
                                        console.log("Send packet #"+args.packet_num+" : [" + args.packet_offset + ":" + args.packet_size + "]\n");
                                        // upload la part de données
                                        http_post_multipart_async(
                                            $(window).navigator("uri",packet_uri),
                                            [
                                                http_text_part("output","xarg"),
                                                http_text_part("io_upload_id",io_upload_id),
                                                http_text_part("packet_num",args.packet_num),
                                                http_text_part("packet_size",args.packet_size),
                                                //base64_data
                                                {
                                                    headers:[
                                                        'Content-Disposition: form-data; name="base64_data"',
                                                        'Content-Type: application/octet-stream',
                                                        'Content-Length: ' + size
                                                    ],
                                                    data:data
                                                }
                                            ],
                                            "form-data",
                                            function(e,context){
                                                if(this.readyState == 4)//DONE
                                                {
                                                    //resultat ?
                                                    var args = xarg_to_object(http_response(this),false);
                                                    if (!args || typeof(args.result) == 'undefined') {
                                                        packetReq.onerror(packetReq);
                                                        return;
                                                    }

                                                    //erreur ?
                                                    if (args.result != "ERR_OK") {
                                                        //failed callback
                                                        packetReq.onfailed(packetReq, args);
                                                        return;
                                                    }

                                                    //success callback
                                                    packetReq.onsuccess(packetReq, args);
                                                }
                                            }
                                        );
                                    }
                                );
                            }
                            //une erreur est survenue
                            else{
                                console.log("checkReq: FAILED\n");
                                callback_failed(args);
                            }
                        },
                        onerror: function (obj) {
                            console.log("checkReq: ERROR\n");
                        },
                        async:false
                    };
            
                    //transfer les paquets
                    $(window).request(
                        "xarg",
                        check_uri,
                        {
                            io_upload_id: io_upload_id,
                            output: 'xarg'
                        },
                        checkReq
                    );
            }
        }
            
       return this;
    };
})(jQuery);
