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
 * Gestionnaire d'entrées-sorties
 * Librairie Javascript
 *
 * WFW Dependences: base.js
 * YUI Dependences: base, wfw, wfw-request, wfw-uri, wfw-navigator, wfw-style, wfw-xarg, wfw-document
*/

YUI.add('wfw-io', function (Y) {
    var wfw = Y.namespace('wfw');
    
    wfw.IO = {
        use: true,
        list: {},
        
        /**
         * @brief Envoie un ou plusieurs fichiers
         * @param DOMNode fileObj Un élément INPUT de type FILE
         * @return bool Résultat de procédure
         */
        sendFileFromElement: function(fileObj,att)
        {
            // upload simple (I.E)
            if (typeof (fileObj.files) == "undefined") {
                if (empty(fileObj.value)) {
                    wfw.puts("sendFileEl (form): Veuillez choisir un fichier à envoyer");
//                    return wfw.Result.set(wfw.Result.Failed, "APP_NO_INPUT_FILE");
                    return false;
                }
                wfw.IO.sendAsForm("file_form", null, att);
            }
            // upload par paquet
            else {
                if (!fileObj.files.length) {
                    wfw.puts("sendFileEl (pck): Veuillez choisir un fichier à envoyer");
//                    return wfw.Result.set(wfw.Result.Failed, "APP_NO_INPUT_FILE");
                    return false;
                }
                wfw.IO.sendAsPacket(fileObj.files[0], att);
            }
            return true;
        },
        /*
            Remarques:
                data    = input[File]
                wfw_id  = text
                wfw_pwd = text
        */
        sendAsForm: function (formid, client_id, att) {
            wfw.puts("wfw.IO.sendAsForm");
            // options 
            var opt = {
                callback:null,
                form_id:"",
                client_pwd:""
            };
            if(typeof(att)!="undefined")
                opt=object_merge(opt,att);

            //obtient la form
            var form=$doc(formid);
            if(input)
            {
                wfw.puts("wfw.ext.upload.sendAsForm: form not found !");
                return;
            }

            /* Insert les champs manquant */
            var fields = wfw.form.get_fields(formid);
            if(typeof(fields["wfw_id"])=="undefined")
            {
                var input=document.createElement("input");
                if(input)
                {
                    objInsertNode(input,form,null,INSERTNODE_END);
                    objSetAtt(input,"type","hidden");
                    objSetAtt(input,"name","wfw_id");
                }
            }
            if(typeof(fields["wfw_pwd"])=="undefined" && !empty(opt.client_pwd))
            {
                var input=document.createElement("input");
                if(input)
                {
                    objInsertNode(input,form,null,INSERTNODE_END);
                    objSetAtt(input,"type","hidden");
                    objSetAtt(input,"name","wfw_pwd");
                }
            }
            /* Définit les champs de la requête 'up.php' */
            fields = {
                wfw_id  : client_id,
                wfw_pwd : opt.client_pwd
            };
            wfw.form.set_fields(formid,fields);
        
            /* demande la creation d'un processus d'upload */
            var param = {
                "onsuccess": function (obj, args) {
                    //prepare la liste
                    var list = {
                        part_remaining: (1),
                        part_count: (1)
                    };
                    //callback ?
                    if(opt.callback)
                        opt.callback(args,"begin",list);
                    //
                    //args.token = token;
                    var simpleUploadResult = object_merge(wfw.ext.upload.onSimpleUploadResult,{
                        options:(opt)
                    });
                    simpleUploadResult.onsuccess(obj,args);
                },
                onfailed: function (obj, args) {
                    //callback ?
                    if(opt.callback)
                        opt.callback(args,"failed");
                    wfw.puts("wfw.ext.upload.sendAsForm: FAILED\n");
                },
                onerror: function (obj) {
                    //callback ?
                    if(opt.callback)
                        opt.callback(null,"error");
                    wfw.puts("wfw.ext.upload.sendAsForm: ERROR\n");
                }
            };

            //envoie la forme dans une iframe est recurere le contenu
            wfw.form.sendFrame(formid,"req/client/up.php",
                function (responseText, param) {
                    //simule un objet requete deja executé
                    var req = $new( wfw.request.REQUEST, {
                        url          : "req/client/up.php",
                        user         : param,
                        status       : 200,
                        response     : responseText,
                    });
                    //appel le callback
                    wfw.utils.onCheckRequestResult_XARG(req);
                },
                param);
        },
        UploadStatus:{
          Begin:"begin",
          Failed:"failed",
          Error:"error",
          Update:"update",
          End:"end"
        },
        /*
        Upload un fichier en paquets
        Paramètres:
            [File]      file         : L'Objet File
            [objet]     opt          : Options supplémentaires de la fonction (voir Paramètres optionnels)
        Paramètres optionnels:
            [function]  callback     : Callback appelé une fois le fichier totalement uploadé: callback(args,state,infos)
            [string]    form_id      : Identificateur de formulaire utilisé pour le résultat
            [string]    client_pwd   : ( Non utilisé ), Mot-de-passe du dossier client
        Retourne:
            [bool] true en cas de succès, false en cas d'erreur
        */
        sendAsPacket: function (file, att) {
            wfw.puts("wfw.IO.sendAsPacket");
        
            // options 
            var opt = {
                callback:function(args,state,infos){},
                form_id:"",
                client_pwd:""
            };
            if(typeof(att)!="undefined")
                opt=object_merge(opt,att);
            
            // reponse de la requete 'begin_upload'
            var infos = null;
            
            // demande la création d'un processus d'upload
            wfw.Request.Add(
                null,
                wfw.Navigator.getURI("begin_upload"),
                {
                    file_size: file.size, 
                    filename: file.name,
                    output: 'xarg'
                },
                wfw.XArg.onCheckRequestResult, 
                {
                    onsuccess: function (obj, args) {
                        infos = copy(args);

                        //callback
                        opt.callback(args,"begin");
                        wfw.puts("wfw.IO.sendAsPacket: BEGIN (io_upload_id="+args.io_upload_id+", "+file.size+" bytes)");
                    },
                    onfailed: function (obj, args) {
                        //callback
                        opt.callback(args,"failed");
                        wfw.puts("wfw.IO.sendAsPacket: FAILED");
                    },
                    onerror: function (obj) {
                        //callback
                        opt.callback(null,"error");
                        wfw.puts("wfw.IO.sendAsPacket: ERROR");
                    }
                },
                false
            );
            
            if(infos==null)
                return false;

            // prepare la requete d'envoi
            var packetReq = {
                name:null,
                url:wfw.Navigator.getURI("packet_upload"),
                args:{
                    io_upload_id: infos.io_upload_id, 
                    packet_num: null,//a redefinir
                    packet_data: null,//a redefinir
                    output: 'xarg'
                },
                callback:wfw.XArg.onCheckRequestResult, 
                user:{
                    onsuccess: function (obj, args) {
                        opt.callback(args,"update");
                        wfw.puts("wfw.IO.sendAsPacket: Packet n°"+obj.args.packet_num+" envoyé");
                        //continue l'upload avec le prochain paquet
                        wfw.Request.Insert(new wfw.Request.REQUEST(copy(checkReq)));
                    },
                    onfailed: function (obj, args) {
                        //une erreur est survenue
                        opt.callback(args,"failed");
                        wfw.puts("wfw.IO.sendAsPacket: FAILED");
                    },
                    onerror: function (obj) {
                        //callback
                        opt.callback(null,"error");
                        wfw.puts("wfw.IO.sendAsPacket: ERROR");
                    }
                },
                async:true
            };
            
            // prepare la requete de verification
            var checkReq = {
                name:null,
                url:wfw.Navigator.getURI("check_upload"),
                args:{
                    io_upload_id: infos.io_upload_id, 
                    filename: file.name,
                    output: 'xarg'
                },
                callback:wfw.XArg.onCheckRequestResult, 
                user:{
                    onsuccess: function (obj, args) {
                        opt.callback(args,"end");
                        wfw.puts("wfw.IO.sendAsPacket: END\n");
                    },
                    onfailed: function (obj, args) {
                        //l'upload n'est pas terminé, on continue
                        if(args.error == "IO_FILE_UNCOMPLETED")
                        {
                            wfw.puts("Continue upload with packet "+args.packet_num);
                            //lit le contenu du fichier
                            wfw.IO.readFileOffset_base64(
                                file,
                                args.packet_offset,
                                args.packet_size,
                                //onLoad
                                function (start, size, data, param) {
                                    wfw.puts("Send packet #"+args.packet_num+" : [" + args.packet_offset + ":" + args.packet_size + "]\n");
                                    var req = object_merge(packetReq,{
                                        args:{
                                            output:"xarg",
                                            io_upload_id: infos.io_upload_id,
                                            packet_num: args.packet_num,
                                            packet_size: args.packet_size,
                                            base64_data: new wfw.HTTP.HTTP_REQUEST_PART({
                                                headers: [
                                                    'Content-Disposition: form-data; name="base64_data"',
                                                    'Content-Type: application/octet-stream',
                                                    'Content-Length: ' + size
                                                ],
                                                data: (data)
                                            })
                                        }
                                    },true);
                                    
                                    wfw.Request.Insert(new wfw.Request.REQUEST(req));
                                }
                            );
                        }
                        //une erreur est survenue
                        else{
                            //callback
                            opt.callback(args,"failed");
                            wfw.puts("wfw.IO.sendAsPacket: FAILED\n");
                        }
                    },
                    onerror: function (obj) {
                        //callback
                        opt.callback(null,"error");
                        wfw.puts("wfw.IO.sendAsPacket: ERROR\n");
                    }
                },
                async:false
            };
            
            //debut l'upload par une premiere verification
            wfw.Request.Insert(new wfw.Request.REQUEST(copy(checkReq)));
            
            return true;
        },
    
        /*
            [ PRIVATE ]
            Callback XARG, progression de l'upload
        */
        onUploadResult: {
            onsuccess: function (obj, args) {
                var list = wfw.ext.upload.list[obj.args.token];
                list[obj.args.offset] = true;
                wfw.puts("wfw.ext.upload.onUploadResult: Receive [" + obj.args.offset + ":" + obj.args.size + "] OK\n");
                wfw.puts("wfw.ext.upload.onUploadResult: filename = [" + obj.args.filename + "]\n");
                list.part_remaining--;
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update",list);
                //terminé
                if (!list.part_remaining) {
                    wfw.puts("wfw.ext.upload.onUploadResult: finalize upload\n");
                    wfw.request.Add(null, "req/client/finalizeUpload.php", obj.args, wfw.utils.onCheckRequestResult_XARG, object_merge({
                        options:this.options
                        },wfw.ext.upload.onFinalizeResult), false);
                }
                else
                    wfw.puts("wfw.ext.upload.onUploadResult: part remaining " + list.part_remaining + " of " + list.part_count + "\n");
            },
            onfailed: function (obj, args) {
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update_failed",null);
                //definit dans la liste
                wfw.ext.upload.list[obj.args.token][obj.args.offset] = false;
                wfw.puts("wfw.ext.upload.onUploadResult: Receive [" + obj.args.offset + ":" + obj.args.size + "] FAILED\n");
            },
            onerror: function (obj) {
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update_error",null);
                //definit dans la liste
                wfw.ext.upload.list[obj.args.token][obj.args.offset] = false;
                wfw.puts("wfw.ext.upload.onUploadResult: Receive [" + obj.args.offset + ":" + obj.args.size + "] ERROR\n");
            }
        },
    
        /*
            [ PRIVATE ]
            Callback XARG, progression de l'upload
        */
        onSimpleUploadResult: {
            onsuccess: function (obj, args) {
                var list = {
                    part_remaining: (0),
                    part_count: (1)
                };
                wfw.puts("wfw.ext.upload.onUploadResult: filename = [" + obj.args.filename + "]\n");
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update",list);
                //terminé
                wfw.puts("wfw.ext.upload.onFinalizeResult: file upload OK\n");
                this.options.callback(args,"done",list);
            },
            onfailed: function (obj, args) {
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update_failed",null);
            },
            onerror: function (obj) {
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"update_error",null);
            }
        },
    
        /*
            [ PRIVATE ]
            Callback XARG, finalize un upload
        */
        onFinalizeResult: {
            onsuccess: function (obj, args) {
                var list = wfw.ext.upload.list[obj.args.token];
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"done",list);
                wfw.puts("wfw.ext.upload.onFinalizeResult: file upload OK\n");
            },
            onfailed: function (obj, args) {
                //callback ?
                if(this.options.callback){
                    alert("failed");
                    this.options.callback(args,"failed");
                }
                wfw.puts("wfw.ext.upload.onFinalizeResult: file upload FAILED\n");
            },
            onerror: function (obj) {
                //callback ?
                if(this.options.callback)
                    this.options.callback(args,"error");
                wfw.puts("wfw.ext.upload.onFinalizeResult: file upload ERROR\n");
            }
        },
    
        /*
            [ PRIVATE ]
            Lit un fragement du fichier encodé en base64
            Parametres:
                [File]      file      : L'Objet File
                [int]       start     : Offset en octets
                [int]       size      : Taille en octets
                [function]  callback  : Callback appelé une fois le fichier totalement uploadé: callback(start, size, data, param)
                [mixed]     param     : Paramètres passés au callback
            Retourne:
                [bool] true en cas de succès, false en cas d'erreur
        */
        readFileOffset_base64: function (file, start, size, callback, param) {
            var reader = new FileReader();
            
            start=parseInt(start);
            size=parseInt(size);
            wfw.puts("wfw.IO.readFileOffset_base64: File size="+file.size+", Read[ofs:"+start+"->"+(start+size)+", size:"+size+"]");

            //tout le fichier ?
            /*if (!start && !size) {
                start = 0;
                size = file.size;
            }
            //restant
            else if (start && !size) {
                size = file.size - start;
            }*/

            if (start+size > file.size){
                wfw.puts("wfw.IO.readFileOffset_base64: Invalid data range !");
                return false;
            }
            
            //wfw.puts("Slice file size=" + size + " start=" + start);
            //Slicer...
            var blob;
            var slice = file.slice || file.webkitSlice || file.mozSlice;
            if (slice)
                blob = slice.call(file, start, start + size, 'application/octet-stream');
            else {
                wfw.puts("wfw.IO.readFileOffset_base64: Slice file is not disponible on your navigator !");
                return false;
            }
            
            if(blob.size != size){
                wfw.puts("wfw.IO.readFileOffset_base64: Invalid blob size !");
                wfw.puts("wfw.IO.readFileOffset_base64: blob.size("+blob.size+") != size("+size+")");
                return false;
            }

            
            //Lit le fichier
            reader.param = param;
            reader.callback = callback;
            reader.onabort = function (evt) {wfw.puts("readFileOffset_base64: Abort event !");}
            reader.onerror = function (evt) {wfw.puts("readFileOffset_base64: Error event !");}
            reader.onloadend = function (evt) {
                if (evt.target.readyState == FileReader.DONE) {
                    //supprime l'entete des données (base64 only)
                    var data = evt.target.result.indexOf(",");
                    if (data != -1) //supprime l'entete
                        data = evt.target.result.substr(data + 1);
                    else
                        data = evt.target.result;
                    /*var last_size = strlen(evt.target.result);
                var last = 6;
                var begin = 0;
                wfw.puts("data size=" + last_size + " first=0x" + (evt.target.result[begin].charCodeAt(0)) + "(" + evt.target.result[begin] + ")" + "last=0x" + (evt.target.result[last].charCodeAt(0)) + "(" + evt.target.result[last] + ")");
                this.callback(start, size, evt.target.result, this.param);*/

                    this.callback(start, size, data, this.param);
                }
            };
            reader.readAsDataURL(blob); //base64 encodé
            //reader.readAsBinaryString(blob);

            return true;
        },
    
        /*
            [ PRIVATE ]
            Lit l'intégralité du fichier encodé en base64
            Parametres:
                [File]      file      : L'Objet File
                [function]  callback  : Callback appelé une fois le fichier totalement uploadé: callback(data, param)
                [mixed]     param     : Paramètres passés au callback
            Retourne:
                [bool] true en cas de succès, false en cas d'erreur
        */
        readFile_base64: function (file, callback, param) {
            var reader = new FileReader();

            reader.param = param;
            reader.callback = callback;
            reader.onloadend = function (evt) {
                //supprime l'entete des données (base64 only)
                var data = evt.target.result.indexOf(",");
                if (data != -1) //supprime l'entete
                    data = evt.target.result.substr(data + 1);
                else
                    data = evt.target.result;
                //callback
                if (evt.target.readyState == FileReader.DONE) {
                    this.callback(data, this.param);
                }
            };

            //Lit le fichier
            reader.readAsDataURL(file); //base64 encodé
            //reader.readAsBinaryString(file);

            return true;
        },

        readFile_base64_IE : function(filePath, callback, param) {
            try {
                var fso = new ActiveXObject("Scripting.FileSystemObject");
			
                alert(filePath);
                var file = fso.GetFile(filePath, 1);
			
                var data = file.ReadAll(ForReading, TristateFalse);
                alert(data);
                file.Close();
            
                callback(data, param);

                return true;
            } catch (e) {
                wfw.puts('wfw.upload.readFile_base64_IE: Unable to access local files');
                if (e.number == -2146827859) {
                    wfw.puts('wfw.upload.readFile_base64_IE: Unable to access local files due to browser security settings. ' + 
                        'To overcome this, go to Tools->Internet Options->Security->Custom Level. ' + 
                        'Find the setting for "Initialize and script ActiveX controls not marked as safe" and change it to "Enable" or "Prompt"'); 
                }
                return false;
            }
        }
    }
}, '1.0', {
    requires:['base', 'cookie', 'wfw','wfw-request','wfw-xml','wfw-uri','wfw-navigator','wfw-http','wfw-xarg']
});
