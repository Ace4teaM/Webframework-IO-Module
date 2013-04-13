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

//loading functions
//ajoutez à ce global les fonctions d'initialisations
Ext.define('MyApp.IO.Upload', {});

/*
 *------------------------------------------------------------------------------------------------------------------
 * @class Image
 * @memberof MyApp.IO.Upload
 * @brief Construit un formulaire d'image
 * @param string io_upload_id Identifiant de l'upload
 *------------------------------------------------------------------------------------------------------------------
 */
Ext.define('MyApp.IO.Upload.Image', {
    extend: 'Ext.panel.Panel',

    config:{
        layout:'border',
        io_begin_upload_uri : null, //io_begin_upload
        io_finalize_upload_uri : null, //io_finalize_upload,
        width:600,
        height:240,
        io_upload_id:null
    },
    
    imageEl : null,
    fileEl : null,
    containerEl : null,
    infosEl : null,
    zoomCtrl : null,
    xCtrl : null,
    yCtrl : null,
    
    //taille originale de l'image (en pixels)
    org_w : 0,
    org_h : 0,
        
    //facteurs
    scale : 1.0, // echelle de taille de l'image (0-1)
    x : 0.5, // position de vue X (0-1)
    y : 0.5, // position de vue Y (0-1)

    initComponent: function()
    {
        var wfw = Y.namespace("wfw");
        var me = this;
        
        this.infosEl = Ext.create('Ext.AbstractComponent', {
            autoEl: { tag: 'div', html:'wxcwx' }
        });

        this.imageEl = Ext.create('Ext.AbstractComponent', {
            autoEl: { tag: 'img', cls: 'title-bar' }
        });

        this.containerEl = {xtype:'container',
            autoEl: { tag: 'div', style: 'width:200px;height:200px;overflow:hidden;border:1px solid black;' },
            width:200,
            height:200,
            items:[this.imageEl]
        };

        this.fileEl = Ext.create('Ext.AbstractComponent', {
            autoEl: { tag: 'input', type: 'file', style:'display:none;', width: 100, height:20 },
            afterRender:function(){
                var fileEl = Y.Node(this.getEl().dom);
                fileEl.on("change",me.uploadFileChange,me,fileEl);
            }
        });

        this.zoomCtrl = Ext.create('Ext.slider.Single', {
            fieldLabel:'Zoom',
            width: 214,
            minValue: 0,
            useTips: false,
            maxValue: 100
        });
        this.zoomCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setScale(newValue/100.0);
            me.updateInfos();
        });

        this.xCtrl = Ext.create('Ext.slider.Single', {
            fieldLabel:'H',
            width: 214,
            minValue: 0,
            useTips: false,
            maxValue: 100
        });
        this.xCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setX(newValue/100.0);
            me.updateInfos();
        });

        this.yCtrl = Ext.create('Ext.slider.Single', {
            fieldLabel:'V',
            width: 214,
            minValue: 0,
            useTips: false,
            maxValue: 100
        });
        this.yCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setY(newValue/100.0);
            me.updateInfos();
        });

        Ext.apply(this, {
            items: [
                {
                    title:"Aperçu",
                    border:false,
                    bodyPadding:6,
                    layout:'vbox',
                    region:"west",
                    width:250,
                    defaults:{
                        width:'100%'
                    },
                    items:[me.containerEl]
                },
                {
                    title:"Paramètres",
                    border:false,
                    bodyPadding:6,
                    layout:'vbox',
                    region:"center",
                    defaults:{
                        width:'100%'
                    },
                    items:[me.fileEl,me.zoomCtrl,me.xCtrl,me.yCtrl,me.infosEl]
                }
            ]
        });

        Ext.apply(this,{
            dockedItems:[{
                xtype: 'toolbar',
                dock: 'right',
                items: [
                    {
                        iconCls: 'wfw_icon open',
                        text: 'Ouvrir...',
                        scope:me,
                        handler:function(){
                            var fileEl = Y.Node(me.fileEl.getEl().dom);
                            Y.Event.simulate(me.fileEl.getEl().dom, 'click');
                        }
                    },
                    {
                        iconCls: 'wfw_icon refresh',
                        text: 'Rétablir',
                        scope:me,
                        handler:function(){
                            me.resetScale();
                        }
                    },
                    '->',
                    {
                        iconCls: 'wfw_icon save',
                        text: 'Sauvegarder',
                        scope:me,
                        handler:function(){ me.applyImage(); }
                    }
                ]
            }]
        });

        if(this.io_begin_upload_uri == null)
            this.io_begin_upload_uri = wfw.Navigator.getURI("io_begin_upload");
        
        if(this.io_finalize_upload_uri == null)
            this.io_finalize_upload_uri = wfw.Navigator.getURI("io_finalize_image_upload");
        
        if(this.io_upload_id)
            this.loadImage(this.io_upload_id);

        this.superclass.initComponent.apply(this, arguments);
    },
    
    constructor: function(config) {
        Ext.apply(this, this.config);
        this.superclass.constructor.call(this,config);
        return this;
    },

    /** le fichier a changé */
    uploadFileChange: function(e,fileEl) {
        var wfw = Y.namespace("wfw");
        var me = this;

        var dlg = Ext.create('MyApp.IO.UploadDialog',{
            fileEl   : fileEl.getDOMNode(),
            begin_upload_uri: me.begin_upload_uri,
            onReady : function(args){
                //charge l'image nouvellement telecharge
                me.loadImage(args.io_upload_id);
            }
        });
        dlg.show();
    },
    
    /** actaulise l'affichage des infos */
    updateInfos : function() {
        var pos = this.toPixelPos();
        //this.infosEl.update('position '+pos.x+','+pos.y+' : '+pos.w+','+pos.h);
        var overflow = this.isOverflow(pos);
        
        this.infosEl.update(
             'position : '+pos.cx+','+pos.cy+'<br/>'
            +'rectangle: '+pos.lx+','+pos.rx+'=>'+pos.ty+','+pos.by+'<br/>'
            +(overflow ? 'invalide' : 'valide')
        );
    },
    
    /** verifie si la position données dépasse les limites de l'image */
    isOverflow : function(pos) {
        if(!pos)
            pos = this.toPixelPos();
        if(pos.lx<0 || pos.ty<0 || pos.rx>=this.org_w || pos.by>=this.org_h)
            return true;
        return false;
    },
    
    /** retourne le rectangle de selection en pixels */
    toPixelPos : function() {
        //facteur de taille du rectangle visible
        var screen_ofs_factor_x = this.containerEl.width / this.org_w;
        var screen_ofs_factor_y = this.containerEl.height / this.org_h;
        //facteur de taille d'un pixel (1+)
        var scale_pixel = this.org_w / (this.org_w * this.scale);
        
        var pos = {
            //position du centre
            cx:parseInt(this.x * this.org_w),
            cy:parseInt(this.y * this.org_h),
            //position du rectangle
            lx:parseInt((this.x-(screen_ofs_factor_x/2.0) * scale_pixel) * this.org_w),
            ty:parseInt((this.y-(screen_ofs_factor_y/2.0) * scale_pixel) * this.org_h),
            rx:parseInt((this.x+(screen_ofs_factor_x/2.0) * scale_pixel) * this.org_w),
            by:parseInt((this.y+(screen_ofs_factor_y/2.0) * scale_pixel) * this.org_h)
        };
        
        return pos;
    },

    /** définit le facteur d'echelle */
    setScale : function(factor) {
        factor = parseFloat(factor);

        if (isNaN(factor) || factor > 1.0)
            factor = 1.0;
        else if (factor < 0.0)
            factor = 0.0;

        var imgEl = Y.Node(this.imageEl.getEl().dom);
        imgEl.set("width",this.org_w * factor);
        imgEl.set("height",this.org_h * factor);
        
        this.scale = factor;
        
        this.setX(this.x);
        this.setY(this.y);
        
        this.zoomCtrl.setValue(factor*100);
    },

    /** définit la position d'affiche sur X */
    setX : function(factor) {
        factor = parseFloat(factor);

        if (isNaN(factor) || factor > 1.0)
            factor = 1.0;
        else if (factor < 0.0)
            factor = 0.0;

        this.x = factor;
        
        var imgEl = Y.Node(this.imageEl.getEl().dom);
        var img_w = (this.scale*this.org_w);
        var img_x = (this.x*img_w);
        var ofs = -(img_w/2.0)+(this.containerEl.width/2.0);
        ofs -= (this.x-0.5)*(img_w);
        imgEl.setStyle("margin-left",ofs);
        
        this.xCtrl.setValue(factor*100);
    },

    /** définit la position d'affiche sur Y */
    setY : function(factor) {
        factor = parseFloat(factor);

        if (isNaN(factor) || factor > 1.0)
            factor = 1.0;
        else if (factor < 0.0)
            factor = 0.0;

        this.y = factor;
        
        var imgEl = Y.Node(this.imageEl.getEl().dom);
        var img_h = (this.scale*this.org_h);
        var img_y = (this.y*img_h);
        var ofs = -(img_h/2.0)+(this.containerEl.height/2.0);
        ofs -= (this.y-0.5)*(img_h);
        imgEl.setStyle("margin-top",ofs);
        
        this.yCtrl.setValue(factor*100);
    },

    /** charge une image uploadé */
    loadImage : function(io_upload_id) {
        var wfw = Y.namespace("wfw");
        var me = this;
        var imgEl = Y.Node(this.imageEl.getEl().dom);
        imgEl.on("load",function(){
            me.org_w = parseInt(this.get("width"));
            me.org_h = parseInt(this.get("height"));
        
            me.resetScale();
        });
        this.img_url = wfw.Navigator.getURI("io_get_data")+"&io_upload_id="+io_upload_id;
        imgEl.set("src",this.img_url);
        this.io_upload_id = io_upload_id;
    },

    /** reinitialise la vue */
    resetScale : function() {
        //ajuste le scaling à la taille de l'image
        var scale;
        if(this.org_w > this.org_h){
            scale = this.containerEl.height / this.org_h;
        }
        else{
            scale = this.containerEl.width / this.org_w;
        }
        
        this.setScale(scale);
        this.setX(0.5);
        this.setY(0.5);
    },
    
    /** applique la transformation */
    applyImage : function() {
        var wfw = Y.namespace("wfw");
        var me = this;
        
        var pos = this.toPixelPos();
        
        // demande la création d'un processus d'upload
        wfw.Request.Add(
            null,
            me.io_finalize_upload_uri,
            {
                io_upload_id: me.io_upload_id,
                lx: pos.lx,
                rx: pos.rx,
                ty: pos.ty,
                by: pos.by,
                output:'xarg'
            },
            wfw.XArg.onCheckRequestResult, 
            {
                onsuccess:function(req,args){
                    MyApp.showResultToMsg(wfw.Result.fromXArg(args));
                },
                onfailed:function(req,args){
                    MyApp.showResultToMsg(wfw.Result.fromXArg(args));
                }
            },
            false
        );
    }
});
