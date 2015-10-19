/**
 * Skripty pro upload dat
 */

'use strict';

var DataUpload=function(){
  this.fileInputElement=null;
  this.uploadFormBlock=null;
  this.uploadConfigBlock=null;
  this.uploadColumnsBlock=null;
  this.uploadProgressBlock=null;
  this.uploadProgressBar=null;
  this.dataServiceUrl=null;
  this.apiKey=null;
  /**
   * @type {FileUploader}
   */
  var fileUploader;
  /**
   * Velikost nahrávaných dat při potřebě získání ukázky
   * @type {number}
   */
  const UPLOAD_PREVIEW_BYTES=300000;
  /**
   * Identifikace nahraného souboru pro generování ukázky...
   * @type {string}
   */
  var previewFileName='';

  /**
   * Funkce pro zobrazení bloku s nahrávacím formulářem
   */
  this.showUploadFormBlock=function(){
    this.uploadConfigBlock.hide();
    this.uploadColumnsBlock.hide();
    this.uploadProgressBlock.hide();
    this.uploadFormBlock.show();
  };
  /**
   * Funkce pro inicializaci konfigurace uploadu
   */
  this.showUploadConfigBlock=function(){
    //zobrazení potřebných bloků
    this.uploadFormBlock.hide();
    this.uploadColumnsBlock.hide();
    this.uploadProgressBlock.hide();
    this.uploadConfigBlock.show();
    //TODO
  };
  this.showColumnsConfigBlock=function(){
    //TODO
  };
  /**
   * Funkce pro upload ukázky dat
   */
  this.reloadPreview=function(){
    if (previewFileName==""){
      this.uploadPreviewData();

    }
    //TODO přenačtení ukázkových dat...
  };

  /**
   * Funkce pro nahrání celého souboru
   */
  this.submitAllData=function(){
    //TODO odeslání všech potřebných dat
    //inicializace file uploaderu
    var self=this;
    fileUploader.onUploadStart=function(){
      //zobrazení upload bloku a spuštění uploadu
      self.uploadFormBlock.hide();
      self.uploadColumnsBlock.hide();
      self.uploadConfigBlock.hide();
      self.uploadProgressBlock.show();
    };
    fileUploader.onUploadFinished=function(){
      //TODO redirect...
      console.log('upload finished -> go to new miner creation...');
    };
    fileUploader.onProgressUpdate=function(){
      var progressState=fileUploader.getProgressState();
      //TODO doplnění pozadí u progress baru
      self.uploadProgressBar.text(Math.round(progressState*100)+'%');
    };
    fileUploader.startUpload('upload',this.getInputParams());
  };

  /**
   * Funkce pro sestavení parametrů pro upload dat...
   */
  this.getInputParams=function(){
    /*FIXME*/
    return {
      "name": "testFile",
      "mediaType": "csv",
      "dbType": "limited",
      "separator": ";",
      "encoding": "utf-8",
      "quotesChar": "'",
      "escapeChar": "\\",
      "locale": "en",
      //"compression": "zip",
      "nullValues": [
        ""
      ],
      "dataTypes": [
        "numeric","numeric","nominal","numeric","numeric","numeric","nominal"
      ]
    };
  };

  this.uploadPreviewData=function(){

  };
  this.init=function(){
    initUploadFormActions();
    initUploadConfigFormActions();
    initFileUploader();
    //zobrazíme první část uploadu
    self.showUploadFormBlock();
  };

  var initUploadFormActions=function(){
    //FIXME nabindování akcí k uploadovacímu formuláři...

  };

  var initUploadConfigFormActions=function(){
    //FIXME nabindování akcí ke konfiguračnímu formuláři...
  };

  /**
   * Funkce pro inicializaci file uploaderu
   */
  var initFileUploader=function(){
    fileUploader=new FileUploader({
      dataServiceUrl:self.dataServiceUrl,
      apiKey:self.apiKey,
      inputElementId:self.fileInputElement.id,
      onProgressUpdate:function(){//TODO
        console.log('progress update..');
      },/*TODO
       onShowMessage: function(){

       },*/
    });
  }
};


$(document).ready(function(){
  //inicializace celé aplikace
  var dataUpload=new DataUpload();
  dataUpload.fileInputElement=$('#fileUploadBlock form').get(0);
  dataUpload.uploadFormBlock=$('#uploadFormBlock');
  dataUpload.uploadConfigBlock=$('#uploadConfigBlock');
  dataUpload.uploadColumnsBlock=$('#uploadColumnsBlock');
  dataUpload.uploadProgressBlock=$('#uploadProgressBlock');
  dataUpload.uploadProgressBar=$('#uploadProgressBlock .progressBar');
  dataUpload.dataServiceUrl=dataServiceUrl;
  dataUpload.apiKey=apiKey;
  dataUpload.init();
});

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


var inputParams={
  "name": "testFile",
  "mediaType": "csv",
  "dbType": "limited",
  "separator": ";",
  "encoding": "utf-8",
  "quotesChar": "'",
  "escapeChar": "\\",
  "locale": "en",
  //"compression": "zip",
  "nullValues": [
    ""
  ],
  "dataTypes": [
    "numeric","numeric","nominal","numeric","numeric","numeric","nominal"
  ]
};


/*testovací skripty pro datovou službu*/
var test = function(){
  //FIXME jen testovací funkce
  var uploaderObject=new FileUploader({
    dataServiceUrl:dataServiceUrl,
    apiKey:apiKey,
    inputElementId:"fileInput",
    onProgressUpdate:function(){
      console.log('progress update..');
    }
  });
  //uploaderObject.startUpload("upload/preview",{"mediaType":"csv","maxLines":20});
  uploaderObject.startUpload("upload",inputParams);

};