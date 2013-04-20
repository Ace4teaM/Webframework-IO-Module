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

Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.util.*',
    'Ext.state.*'
    ]);


//loading functions
//ajoutez à ce global les fonctions d'initialisations
Ext.define('MyApp.IO.Upload', {});

/*------------------------------------------------------------------------------------------------------------------*/
//
// Panneau d'edition principal
//
/*------------------------------------------------------------------------------------------------------------------*/
Ext.define('MyApp.IO.Upload.Editor', {
    name: 'Unknown',
    extend: 'Ext.panel.Panel',
    
    store : null,
    grid : null,

    newBtn : null,
    deleteBtn : null,
    
    config:{
        autoScroll:false,
        title:"Uploads",
        border:false,
        bodyPadding:6,
        closeAction:'destroy',
        layout:'vbox',
        defaults:{
            width:'100%'
        }
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
        
        var table = "io_upload";
        var cols  = ["io_upload_id", "filename", "content_type", "output_path", "upload_path", "upload_client_ip"];

        //obtient les données
        var myData = wfw.DataModel.fetchData(table,cols);

        // crée le model de données
        var store = MyApp.DataModel.createArrayStore(cols,myData);
        this.store = store;
        // crée la defintion des colonnes
        this.grid = Ext.create("MyApp.DataModel.Grid",{
            store:store,
            data:myData,
            cols:cols
        });

        // newBtn
        this.newBtn = Ext.create('Ext.Button',{
            text:"Nouveau",
            iconCls: 'wfw_icon new',
            handler:this.onNewClick,
            scope:me
        });
 
        // deleteBtn
        this.deleteBtn = Ext.create('Ext.Button',{
            text:"Supprimer",
            iconCls: 'wfw_icon delete',
            disabled: true,
            handler:this.onDeleteClick,
            scope:me
        });

        //Boutons
        Ext.apply(this, {
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                frame: true,
                items: [
                    this.newBtn,
                    '-',
                    this.deleteBtn
                ]
            }]
        });
        
        //items
        Ext.apply(this, {
            items: [
            this.grid
            ]
        });

        this.superclass.initComponent.apply(this, arguments);
        
        //initialise les evenements
        this.grid.getSelectionModel().on('selectionchange', this.onSelectChange, this);
    },

    onSelectChange: function(selModel, selections){
        //alert(selections.length ? false : true);
        this.deleteBtn.setDisabled(selections.length ? false : true);
    },

    onDeleteClick: function(){
        var selection = this.grid.getSelectionModel().getSelection()[0];
        if (selection) {
            this.store.remove(selection);
        }
    },

    onNewClick: function(){
    }

});


/*------------------------------------------------------------------------------------------------------------------*/
//
// Initialise le layout
//
/*------------------------------------------------------------------------------------------------------------------*/
Ext.apply(MyApp.IO.Upload,{onInitLayout: function(Y){

    var wfw = Y.namespace("wfw");
    var g = MyApp.global.Vars;
    
    //var form = Ext.create('MyApp.DataModel.FieldsForm',{wfw_fields:[{id:'content_type'}]});
    var editor = Ext.create('MyApp.IO.Upload.Editor');

    var wfw = Y.namespace("wfw");
    var g = MyApp.global.Vars;

    //l'élément de résultat n'est plus utilisé
    Y.Node.one("#result").hide();
    
    // Nord
    g.statusPanel = Ext.create('Ext.Panel', {
        header:false,
        layout: 'hbox',
        region: 'north',     // position for region
        split: true,         // enable resizing
        margins: '0 5 5 5',
        /*html: Y.Node.one("#menu").get("innerHTML")*/
        items: [{
            header:false,
            border: false,
            width:200,
            contentEl: Y.Node.one("#header").getDOMNode()
        },{
            header:false,
            width:"100%",
            border: false,
            contentEl: Y.Node.one("#status").getDOMNode()
        }],
        renderTo: Ext.getBody()
    });

    // Ouest
    g.menuPanel = Ext.create('Ext.Panel', {
        title: 'Menu',
        layout: {
            // layout-specific configs go here
            type: 'accordion',
            titleCollapse: false,
            animate: true,
            activeOnTop: true
        },
        region: 'west',     // position for region
        width: 200,
        split: true,         // enable resizing
        margins: '0 5 5 5',
        /*html: Y.Node.one("#menu").get("innerHTML")*/
        items: [{
            title: 'Administrateur',
            contentEl: Y.Node.one("#menu1").getDOMNode()
        },{
            title: 'Visiteur',
            contentEl: Y.Node.one("#menu2").getDOMNode()
        },{
            title: 'Utilisateur',
            contentEl: Y.Node.one("#menu3").getDOMNode()
        }],
        renderTo: Ext.getBody()
    });

    // Sud
    g.footerPanel = Ext.create('Ext.Panel', {
        header :false,
        //title: 'Pied de page',
        region: 'south',     // position for region
        split: true,         // enable resizing
        margins: '0 5 5 5',
        contentEl: Y.Node.one("#footer").getDOMNode()
    });

    // Centre
    g.contentPanel = Ext.create('Ext.Panel', {
        header :false,
        //title: 'Content',
        region: 'center',     // position for region
        split: true,         // enable resizing
        margins: '0',
        layout: 'fit',
        autoScroll:true,
        defaults:{
            header:false,
            border: false,
            width:"100%"
        },
        items: [editor],
        renderTo: Ext.getBody()
    });

    //viewport
    g.viewport = Ext.create('Ext.Viewport', {
        layout: 'border',
        items: [g.contentPanel,g.menuPanel,g.statusPanel,g.footerPanel]
    });
}});
