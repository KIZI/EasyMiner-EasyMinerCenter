/**
 * Skripty pro upload dat
 */
//TODO remove console.log(...);
'use strict';

var DataUpload=function(){
  this.fileInputElement=null;
  this.jqElements={
    uploadFormBlock:    null,
    uploadConfigBlock:  null,
    uploadColumnsBlock: null,
    uploadProgressBlock:null,
    uploadProgressBar:  null,
    uploadColumnsListBlock:null,

    databaseTypeInput: null,
    tableNameInput: null,
    escapeCharacterInput: null,
    delimiterInput: null,
    nullValueInput: null,
    encodingInput: null,
    enclosureInput: null,
    localeInput: null
  };
  this.dataServiceUrl=null;
  this.apiKey=null;
  this.previewUrl=null;
  this.uploadPreviewDataUrl=null;
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
  var columnNames=[];
  var columnDataTypes=[];
  var previewRows=[];
  var self=this;

  /**
   * Funkce vracející vyplněnou URL pro načtení náhledu
   * @returns {string}
   */
  var getPreviewUrl=function(reloadParams){
    var result=self.previewUrl;
    var inputParams=self.getInputParams();
    result=result
      .replace('__FILE__',encodeURIComponent(previewFileName))
      .replace('__ENCODING__',encodeURIComponent(inputParams.encoding))
      .replace('__ENCLOSURE__',encodeURIComponent(inputParams.quotesChar))
      .replace('__ESCAPE_CHARACTER__',encodeURIComponent(inputParams.escapeChar))
      .replace('__NULL_VALUE__',encodeURIComponent(inputParams.nullValues[0]))
      .replace('__LOCALE__',encodeURIComponent(inputParams.locale));
    if (reloadParams){
      result=result.replace('__DELIMITER__',encodeURIComponent(inputParams.separator));
    }
    return result;
  };

  /**
   * Funkce pro zobrazení bloku s nahrávacím formulářem
   */
  this.showUploadFormBlock=function(){
    this.jqElements.uploadConfigBlock.hide();
    this.jqElements.uploadColumnsBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadFormBlock.show();
  };
  /**
   * Funkce pro inicializaci konfigurace uploadu
   */
  this.showUploadConfigBlock=function(){
    //todo remove
    //TODO validace nahraných dat...
    //zobrazení potřebných bloků
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadColumnsBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.show();
  };

  /**
   * Funkce pro vygenerování formuláře pro konfiguraci datových sloupců a jeho zobrazení
   */
  this.showColumnsConfigBlock=function(){console.log('showColumnsConfigBlock');
    //připravení položek příslušného formuláře...
    //TODO připravení příslušných položek formuláře...
    var listBlock=this.jqElements.uploadColumnsListBlock;
    var listBlockTable=$('<table><tr><th>'+'Column name'+'</th><th>'+'Data type'+'</th><th>'+'Values'+'</th></tr></table>');
    console.log(columnNames);
    for (var i in columnNames){
      console.log('column '+i);
      //položka konkrétního sloupce
      var columnDetailsTr=$('<tr></tr>');
      var nameInput=$('<input type="text" id="column_'+i+'_name" required />').val(columnNames[i]);//TODO připojení kontrol
      columnDetailsTr.append($('<td></td>').html(nameInput));
      var dataTypeSelect=$('<select id="column_'+i+'_type"><option value="nominal">nominal [ABC]</option><option value="nominal">numerical [123]</option><option value="null">--ignore--</option></select>');
      dataTypeSelect.val(columnDataTypes[i]);
      columnDetailsTr.append($('<td></td>').html(dataTypeSelect));
      var valuesTd=$('<td class="values"></td>');
      //náhled hodnot
      var previewedRows=0;
      var previewData=[];
      for (var row in previewRows){
        if (previewedRows>10){break;}
        var rowData=previewRows[row];
        if (rowData.hasOwnProperty(i)){
          previewData.push(rowData[i]);
        }
      }
      valuesTd.text(previewData.join(" | "));
      columnDetailsTr.append(valuesTd);
      listBlockTable.append(columnDetailsTr);
    }
    listBlock.html(listBlockTable);//přiřazení tabulky do příslušného obsahu
    console.log(listBlock);
    //zobrazení potřebných bloků
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.hide();
    this.jqElements.uploadColumnsBlock.show();
  };

  /**
   * Funkce pro upload ukázky dat
   */
  this.reloadPreview=function(reloadParams){
    if (previewFileName==""){
      this.uploadPreviewData();
      return;
    }

    $.getJSON(getPreviewUrl(reloadParams),function(data){
      if (reloadParams){
        //FIXME nastavení parametrů (včetně původních hodnot, které jsou vráceny v detailech uploadu)
        self.showUploadConfigBlock();
      }
      //TODO připravení příslušných dat...
      //TODO...
      columnNames=data['columnNames'];
      columnDataTypes=data['dataTypes'];
      previewRows=data['data'];
      self.showColumnsConfigBlock();//XXX remove
    });
  };

  /**
   * Funkce pro nahrání celého souboru
   */
  this.submitAllData=function(){
    //TODO odeslání všech potřebných dat
    //inicializace file uploaderu
    fileUploader.onUploadStart=function(){
      //zobrazení upload bloku a spuštění uploadu
      self.jqElements.uploadFormBlock.hide();
      self.jqElements.uploadColumnsBlock.hide();
      self.jqElements.uploadConfigBlock.hide();
      self.jqElements.uploadProgressBlock.show();
    };
    fileUploader.onUploadFinished=function(){
      //TODO redirect...
      console.log('upload finished -> go to new miner creation...');
    };
    fileUploader.onProgressUpdate=function(){
      var progressState=fileUploader.getProgressState();
      //TODO doplnění pozadí u progress baru
      self.jqElements.uploadProgressBar.text(Math.round(progressState*100)+'%');
    };
    fileUploader.startUpload('upload',this.getInputParams());
  };

  /**
   * Funkce pro sestavení parametrů pro upload dat...
   * @returns {{name: string, mediaType: string, dbType: string, separator: string, encoding: string, quotesChar: string, escapeChar: string, locale: string, nullValues: string[], dataTypes: string[]}}
   */
  this.getInputParams=function(){
    var result={
      "name": "testFile",//TODO odkud bude brán název souboru?
      "mediaType": "csv",
      "dbType": this.jqElements.databaseTypeInput.val(),//TODO odkud bude brán typ databáze?
      "separator": this.jqElements.delimiterInput.val(),
      "encoding": this.jqElements.encodingInput.val(),
      "quotesChar": this.jqElements.enclosureInput.val(),
      "escapeChar": this.jqElements.escapeCharacterInput.val(),
      "locale": this.jqElements.localeInput.val(),
      nullValues: [this.jqElements.nullValueInput.val()],
      columnNames: columnNames,
      dataTypes: columnDataTypes
    };
    var compression=false;/*TODO*/
    if (compression){
      result['compression']='zip';
    }
    return result;
  };

  /**
   * Funkce pro upload ukázkových dat
   */
  this.uploadPreviewData=function(){
    //region upload ukázkových dat
    var fileReader=new FileReader();
    fileReader.onload=function(){
      jQuery.ajax(self.uploadPreviewDataUrl,{
        type: 'post',
        data: fileReader.result,
        contentType: 'text/plain',
        dataType: 'json',
        success: function(result){
          //soubor byl nahrán, chceme zobrazit ukázku dat...
          previewFileName=result.file;
          self.reloadPreview(true);
        },
        error: function(xhr, status, error){
          //došlo k chybě - zobrazíme info o chybě a následně stornujeme posílání
          //FIXME zpráva o chybě...
        }
       });
    };
    fileReader.onerror=function(){
      //FIXME zpráva o chybě při přístupu k souboru...
    };
    var file=this.fileInputElement.files[0];
    fileReader.readAsBinaryString(file.slice(0,UPLOAD_PREVIEW_BYTES));
    //endregion
  };

  this.init=function(){
    initUploadFormActions();
    initUploadConfigFormActions();
    initFileUploader();
    //zobrazíme první část uploadu
    self.showUploadFormBlock();
  };

  var initUploadFormActions=function(){
    //TODO kontrola, jestli je k dispozici soubor...
    self.jqElements.uploadFormBlock.find('form').find('input[type="submit"]').click(function(e){
      e.preventDefault();
      e.stopPropagation();
      //načteme preview, posléze dojde k zobrazení konfigurace importu
      self.reloadPreview(true);
    });
  };

  var initUploadConfigFormActions=function(){
    var changeFunction=function(){
      //změna hodnoty inputu (jeho opuštění)
      if (!$(this).hasClass(LiveForm.options.controlErrorClass)){
        if ($(this).getAttribute('data-old-value')!=$(this).val()){
          $(this).setAttr('data-old-value',$(this).val());
          console.log("no error -> reload preview...");
          self.reloadPreview();
        }
      }
    };
    self.jqElements.uploadConfigBlock.find('select').change(changeFunction);
    self.jqElements.uploadConfigBlock.find('input[type="text"]').blur(changeFunction);
    self.jqElements.uploadConfigBlock.find('input[type="submit"]').click(function(){//TODO zkontrolovat připojení k submit tlačítku
      console.log("submitted");
    });
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
  var uploadFormBlock=$('#uploadFormBlock');
  var uploadConfigBlock=$('#uploadConfigBlock');
  dataUpload.fileInputElement=uploadFormBlock.find('form input[type="file"]').get(0);

  dataUpload.jqElements={
    uploadFormBlock:    uploadFormBlock,

    uploadConfigBlock:  uploadConfigBlock,

    uploadColumnsBlock: $('#uploadColumnsBlock'),
    uploadColumnsListBlock: $('#uploadColumnsListBlock'),

    uploadProgressBlock:$('#uploadProgressBlock'),
    uploadProgressBar:  $('#uploadProgressBlock .progressBar'),
    // TODO doplnění jquery selectorů
     databaseTypeInput: uploadFormBlock.find('[name="dbType"]'),
     tableNameInput: null,//XXX
     escapeCharacterInput: uploadConfigBlock.find('[name="escape"]'),
     delimiterInput: uploadConfigBlock.find('[name="separator"]'),
     nullValueInput: uploadConfigBlock.find('[name="nullValue"]'),
     encodingInput: uploadConfigBlock.find('[name="encoding"]'),
     enclosureInput: uploadConfigBlock.find('[name="enclosure"]'),
     localeInput: uploadConfigBlock.find('[name="locale"]')
    //
  };


  dataUpload.dataServiceUrl=dataServiceUrl;
  dataUpload.apiKey=apiKey;
  dataUpload.previewUrl=previewUrl;
  dataUpload.uploadPreviewDataUrl=uploadPreviewDataUrl;
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