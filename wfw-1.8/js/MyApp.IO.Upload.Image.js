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
 * @brief Construit un formulaire d'image
 * @param array wfw_fields Liste des définitions de champs (voir MyApp.DataModel.makeField)
 *------------------------------------------------------------------------------------------------------------------
 */
Ext.define('MyApp.IO.Upload.Image', {
    extend: 'Ext.panel.Panel',

    config:{
        layout:'border',
        begin_upload_uri : null, //io_begin_upload
        finalize_upload_uri : null, //io_finalize_upload,
        width:600,
        height:300,
        io_upload_id:null
    },
    
    imageEl : null,
    fileEl : null,
    containerEl : null,
    zoomCtrl : null,
    xCtrl : null,
    yCtrl : null,
    
    //taille originale de l'image (en pixels)
    org_w : 0,
    org_h : 0,
        
    //taille originale de l'image (en pixels)
    scale : 1.0,
    x : 0.5,
    y : 0.5,
        
    initComponent: function()
    {
        var wfw = Y.namespace("wfw");
        var me = this;
        
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
            autoEl: { tag: 'input', type: 'file', width: 100, height:20 }
        });

        this.zoomCtrl = Ext.create('Ext.slider.Single', {
            width: 214,
            minValue: 0,
            hideLabel: true,
            useTips: false,
            maxValue: 100
        });
        this.zoomCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setScale(newValue/100.0);
        });

        this.xCtrl = Ext.create('Ext.slider.Single', {
            width: 214,
            minValue: 0,
            hideLabel: true,
            useTips: false,
            maxValue: 100
        });
        this.xCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setX(newValue/100.0);
        });

        this.yCtrl = Ext.create('Ext.slider.Single', {
            width: 214,
            minValue: 0,
            hideLabel: true,
            useTips: false,
            maxValue: 100
        });
        this.yCtrl.on("change",function( slider, newValue, thumb, eOpts ){
            me.setY(newValue/100.0);
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
                    title:"Parametres",
                    border:false,
                    bodyPadding:6,
                    layout:'vbox',
                    region:"center",
                    defaults:{
                        width:'100%'
                    },
                    items:[me.fileEl,me.zoomCtrl,me.xCtrl,me.yCtrl]
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
                            //fileEl.simulate("click");
                            Y.Event.simulate(me.fileEl.getEl().dom, 'change');
                        }
                    },
                    {
                        iconCls: 'wfw_icon config',
                        text: 'Rétablir',
                        scope:me,
                        handler:function(){}
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

        if(this.begin_upload_uri == null)
            this.begin_upload_uri = wfw.Navigator.getURI("io_begin_upload");
        
        if(this.finalize_upload_uri == null)
            this.finalize_upload_uri = wfw.Navigator.getURI("io_finalize_upload");
        
        if(this.io_upload_id)
            this.loadImage(this.io_upload_id);

        this.superclass.initComponent.apply(this, arguments);
    },
    
    constructor: function(config) {
        Ext.apply(this, this.config);
        this.superclass.constructor.call(this,config);
        return this;
    },
    
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

    loadImage : function(io_upload_id) {
        var wfw = Y.namespace("wfw");
        var me = this;
        var imgEl = Y.Node(this.imageEl.getEl().dom);
        imgEl.on("load",function(){
            me.org_w = parseInt(this.get("width"));
            me.org_h = parseInt(this.get("height"));
        
            me.resetScale();
        });
        imgEl.set("src",wfw.Navigator.getURI("io_get_data")+"&io_upload_id="+io_upload_id);
        this.io_upload_id = io_upload_id;
    },

    resetScale : function() {
        this.setScale(1.0);
        this.setX(0.5);
        this.setY(0.5);
    },
    
    applyImage : function() {
        var wfw = Y.namespace("wfw");
        var me = this;
        
        // demande la création d'un processus d'upload
        wfw.Request.Add(
            null,
            wfw.Navigator.getURI("io_finalize_image_upload"),
            {
                io_upload_id: me.io_upload_id,
                x1f: 0.2,
                y1f: 0.2,
                x2f: 0.8,
                y2f: 0.8,
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
    