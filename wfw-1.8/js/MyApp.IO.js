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

Ext.define('MyApp.IO', {});

/**
 *------------------------------------------------------------------------------------------------------------------
 * @brief Ouvre un dialogue d'upload
 * 
 * # Example
 *
 *     @code{.js}
 *     Ext.create('MyApp.Writer.OpenDialog', {
 *         callback:function(data){
 *              data.writer_document_id;
 *              data.doc_title;
 *              data.doc_content;
 *              data.content_type;
 *         },
 *         filter_type : 'text/html' // proposer uniquement les documents HTML
 *     });
 *     @endcode
 *------------------------------------------------------------------------------------------------------------------
 */
Ext.define('MyApp.IO.UploadDialog', {
    /**
     * @param {String} filter_type
     * Specifie le type de contenu à ouvrir
     */
    require:[
        'Ext.ProgressBar.*'
    ],
    
    extend: 'Ext.window.Window',

    progressBar:null,
    closeBtn:null,

    config:{
        title: 'Téléchargement...',
        width: 600,
        layout: 'fit',
        closable: true,
        modal:true,
        callback:function(data){},
        fileEl: null
    },

    constructor: function(config) {
        Ext.apply(this, this.config);
        this.superclass.constructor.call(this,config);
        return this;
    },

    initComponent: function()
    {
        var wfw = Y.namespace("wfw");
        var me=this;
            
        // onClose
        this.closeBtn = Ext.create('Ext.Button',{
            text:"Annuler",
            handler:function(){
                me.close();
            }
        });
             
        // progressBar
        this.progressBar = Ext.create('Ext.ProgressBar',{
            height:20
        });

        //Boutons
        Ext.apply(this, {
            items: [
                this.progressBar
            ]
        });
        
        //Boutons
        Ext.apply(this, {
            buttons: [
                '->',
                this.closeBtn
            ]
        });

        wfw.IO.sendFileFromElement(this.fileEl,{
            callback:function(args,status){
                switch(status){
                    case wfw.IO.UploadStatus.Begin:
                        me.progressBar.updateProgress(0,'Préparation...');
                        me.part = 0;
                        me.max_part = parseInt(args.packet_count);
                        wfw.puts(me.infos);
                        break;
                    case wfw.IO.UploadStatus.Update:
                        me.progressBar.updateProgress(1.0/me.max_part * parseFloat(me.part),'Téléchargement de par ('+me.part+' sur '+me.max_part+')...');
                        me.part++;
                        break;
                    case wfw.IO.UploadStatus.End:
                        me.progressBar.updateProgress(1.0/me.max_part * parseFloat(me.part),'Téléchargement terminé ('+me.max_part+' parts envoyés)');
                        break;
                    case wfw.IO.UploadStatus.Failed:
                        me.progressBar.updateText('Echec du téléchargement');
                        break;
                    case wfw.IO.UploadStatus.Error:
                        me.progressBar.updateText('Erreur de téléchargement');
                        break;
                }
            }
        });
        
        //ok
        this.superclass.initComponent.apply(this, arguments);
    }
});
