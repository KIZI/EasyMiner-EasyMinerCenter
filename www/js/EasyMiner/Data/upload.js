/**
 * Skripty pro upload dat
 */
'use strict';

var DataUpload=function(){
  this.fileInputElement=null;
  this.jqElements={
    flashMessages: null,

    uploadFormBlock:    null,
    uploadConfigBlock:  null,
    uploadConfigPreviewBlock: null,
    uploadColumnsBlock: null,
    uploadProgressBlock:null,
    uploadProgress:null,
    uploadProgressBar:  null,
    uploadProgressMessage: null,
    uploadColumnsListBlock:null,

    nameInput: null,
    databaseTypeInput: null,
    escapeCharacterInput: null,
    delimiterInput: null,
    nullValueInput: null,
    encodingInput: null,
    enclosureInput: null,
    localeInput: null
  };
  this.dataServiceUrlsByDbTypes=null;
  this.apiKey=null;
  this.previewUrl=null;
  this.uploadPreviewDataUrl=null;
  this.zipSupport=false;
  this.uploadFinishUrl=null;
  this.fileCompression='';
  /**
   * @type {FileUploader}
   */
  var fileUploader;
  /**
   * Velikost nahrávaných dat při potřebě získání ukázky
   * @type {number}
   */
  const UPLOAD_PREVIEW_BYTES=100000;
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
    //pokud by mělo dojít k přenačtení parametrů, tak nebudeme uvádět následující parametry
    if (reloadParams==true){
      result=result.replace('__DELIMITER__','');
    }else{
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
    //skrytí případných flash zpráv
    this.jqElements.flashMessages.hide();
    //vygenerování výchozího jména uploadovaného datasource
    this.processFileName(this.fileInputElement.files[0].name);
    //zobrazení potřebných bloků
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadColumnsBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.show();
  };

  /**
   * Funkce pro zpracování jména nahrávaného souboru - určení komprese a určení jména nahrávané tabulky
   * @param filename : string
   */
  this.processFileName=function(filename){
    var lastDotPosition=filename.lastIndexOf('.');
    var name=filename.substring(0,lastDotPosition);
    var ext =filename.substring(lastDotPosition+1);
    //zpracování jména souboru na název tabulky v DB
    name=seoUrl(name);
    ext=ext.toLowerCase();
    //přiřazení získaných hodnot do inputu a do proměnné s info o kompresi
    this.jqElements.nameInput.val(name);
    switch (ext){
      case 'zip':
        this.fileCompression='zip';
        break;
      case 'gzip':
      case 'gz':
      case 'tgz':
        this.fileCompression='gzip';
        break;
      case 'bzip':
      case 'bz2':
        this.fileCompression='bzip2';
        break;
      default:
        this.fileCompression='';
    }
  };

  /**
   * Funkce pro vygenerování formuláře pro konfiguraci datových sloupců a jeho zobrazení
   */
  this.showColumnsConfigBlock=function(){
    const VALIDATION_ATTRIBUTES='required data-nette-rules=\'[{"op":":filled","msg":"This field is required."},{"op":":UniqueNamesValidator","msg":"This name is already used."},{"op":":pattern","msg":"Attribute name can contain only letters, numbers and _ and has start with a letter.","arg":"[a-zA-Z]{1}\\\\w*"}]\' pattern="[a-zA-Z]{1}\\w*" title="Attribute name can contain only letters, numbers and _ and has start with a letter."';

    //připravení položek příslušného formuláře...
    var listBlock=this.jqElements.uploadColumnsListBlock;
    var listBlockTable=$('<table><tr><th>'+'Column name'+'</th><th>'+'Data type'+'</th><th>'+'Values from first rows...'+'</th></tr></table>');
    for (var i in columnNames){
      //položka konkrétního sloupce
      var columnDetailsTr=$('<tr></tr>');
      var nameInput=$('<input type="text" id="column_'+i+'_name" '+VALIDATION_ATTRIBUTES+' />').val(columnNames[i]).addClass('columnName');
      columnDetailsTr.append($('<td></td>').html(nameInput));
      var dataTypeSelect=$('<select id="column_'+i+'_type"><option value="nominal">nominal [ABC]</option><option value="numeric">numerical [123]</option><option value="null">--ignore--</option></select>');
      dataTypeSelect.val(columnDataTypes[i]);
      columnDetailsTr.append($('<td></td>').html(dataTypeSelect));
      var valuesTd=$('<td class="values"></td>');
      //náhled hodnot
      var previewedRows=0;
      var previewData=[];
      var valuesTdDiv=$('<div></div>');
      valuesTd.append(valuesTdDiv);
      for (var row in previewRows){
        if (previewedRows>10){break;}
        var rowData=previewRows[row];
        if (rowData.hasOwnProperty(i)){
          valuesTdDiv.append($('<span></span>').text(rowData[i]));
        }
      }
      columnDetailsTr.append(valuesTd);
      listBlockTable.append(columnDetailsTr);
    }
    listBlock.html(listBlockTable);//přiřazení tabulky do příslušného obsahu
    //zobrazení potřebných bloků
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.hide();
    this.jqElements.uploadColumnsBlock.show();

    Nette.initForm(self.jqElements.uploadColumnsBlock.find('form').get(0));
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
      //nastavení příslušných proměnných
      columnNames=data['columnNames'];
      columnDataTypes=data['dataTypes'];
      previewRows=data['data'];
      //nastavení hodnot ve formuláři dle vyhodnocení na serveru
      self.jqElements.delimiterInput.prop('data-old-value',data.config.delimiter);
      self.jqElements.delimiterInput.val(data.config.delimiter);
      self.jqElements.enclosureInput.prop('data-old-value',data.config.enclosure);
      self.jqElements.enclosureInput.val(data.config.enclosure);
      self.jqElements.encodingInput.prop('data-old-value',data.config.encoding);
      self.jqElements.encodingInput.val(data.config.encoding);
      self.jqElements.escapeCharacterInput.prop('data-old-value',data.config.escapeCharacter);
      self.jqElements.escapeCharacterInput.val(data.config.escapeCharacter);
      self.jqElements.localeInput.prop('data-old-value',data.config.locale);
      self.jqElements.localeInput.val(data.config.locale);
      self.jqElements.nullValueInput.prop('data-old-value',data.config.nullValue);
      self.jqElements.nullValueInput.val(data.config.nullValue);

      if (reloadParams){
        self.showUploadConfigBlock();
      }
      //vypsání hodnot sloupců
      var previewTable=$('<table></table>');
      var columnsCount=0;
      //názvy sloupců
      var tr=$('<tr></tr>');
      for (var columnI in columnNames){
        var th=$('<th></th>');
        th.text(columnNames[columnI]);
        tr.append(th);
        columnsCount++;
      }
      previewTable.append(tr);
      //hodnoty jednotlivých řádků
      for (var rowI in previewRows){
        var tr=$('<tr></tr>');
        for (var columnI=0;columnI<columnsCount;columnI++){
          var td=$('<td></td>');
          td.text(previewRows[rowI][columnI]);
          tr.append(td);
        }
        previewTable.append(tr);
      }
      self.jqElements.uploadConfigPreviewBlock.html(previewTable);
    });
  };

  /**
   * Funkce pro nahrání celého souboru
   */
  this.submitAllData=function(){
    //inicializace file uploaderu
    fileUploader.setDataServiceUrl(this.dataServiceUrlsByDbTypes[self.jqElements.databaseTypeInput.val()]);//nastavení URL dle zvoleného typu databáze
    fileUploader.onUploadStart=function(){
      //zobrazení upload bloku a spuštění uploadu
      self.jqElements.uploadFormBlock.hide();
      self.jqElements.uploadColumnsBlock.hide();
      self.jqElements.uploadConfigBlock.hide();
      self.jqElements.uploadProgressBlock.show();
      self.jqElements.uploadProgress.show();
    };
    fileUploader.onFileSent=function(){
      self.jqElements.uploadProgress.hide();
    };
    fileUploader.onUploadStop=function(){
      showMessage('Upload stopped.','warn');
      //zrušíme propojení funkcí z uploaderu
      fileUploader.onShowMessage=function(){};
      fileUploader.onProgressUpdate=function(){};
      //skryjeme progress bar
      self.jqElements.uploadProgress.hide();
    };
    fileUploader.onUploadFinished=function(result){
      self.sendFinalRequest(result);
    };
    fileUploader.onProgressUpdate=function(){
      var progressState=fileUploader.getProgressState();
      var percents=Math.round(progressState*100);
      self.jqElements.uploadProgressBar.html('<div class="bg" style="width:'+percents+'%;"></div><div>File upload: '+percents+'%</div>');
    };
    updateColumnNamesAndTypesFromConfigForm();
    fileUploader.startUpload('upload',this.getInputParams());
  };

  /**
   * Funkce pro aktualizaci přehledu požadovaných názvů sloupců a datových typů
   */
  var updateColumnNamesAndTypesFromConfigForm=function(){
    //načteme data z formuláře
    var columnsData=[];
    self.jqElements.uploadColumnsListBlock.find('input[type="text"]').each(function(){
      var thisItem=$(this);
      var nameArr = thisItem.attr('id').split('_');
      if (nameArr[0]=='column' && nameArr.hasOwnProperty('2') && nameArr[2]=='name'){
        columnsData.push([thisItem.val(), $('#column_'+nameArr[1]+'_type').val()]);
      }
    });

    //připravíme pole s názvy a datovými typy pro upload
    columnNames=[];
    columnDataTypes=[];
    jQuery.each(columnsData,function(){
      var thisItem=$(this);
      columnNames.push(thisItem[0]);
      if (thisItem[1]=='nominal' || thisItem[1]=='numeric'){
        columnDataTypes.push(thisItem[1]);
      }else{
        columnDataTypes.push(null);
      }
    });
  };

  /**
   * Funkce pro odeslání finálního requestu
   * @param dataServiceResponse : object
   */
  this.sendFinalRequest=function(dataServiceResponse){
    //odeslání requestu pro finalizaci nahrávání dat (vytvoření datasetu)
    jQuery.ajax(self.uploadFinishUrl,{
      type: 'post',
      data: {
        'uploadConfig': JSON.stringify(self.getInputParams()),
        'dataServiceResult': JSON.stringify(dataServiceResponse)
      },
      success: function(result){
        if (result.hasOwnProperty('message')){
          showMessage(result.message, 'info');
        }
        if (result.hasOwnProperty('redirect')){
          location.href=result.redirect;
        }
      },
      error: function(xhr, status, error){
        //došlo k chybě - zobrazíme info o chybě a následně stornujeme posílání
        showMessage("Upload failed: "+error, 'error');
      }
    });
  };

  /**
   * Funkce pro zobrazení info zprávy
   * @param message : string
   * @param type : string
   */
  var showMessage=function(message, type){
    var messageElement=$('<div class="'+type+'"></div>').html(message);
    self.jqElements.uploadProgressMessage.html(messageElement);
  };

  /**
   * Funkce pro sestavení parametrů pro upload dat...
   * @returns {{name: string, mediaType: string, dbType: string, separator: string, encoding: string, quotesChar: string, escapeChar: string, locale: string, nullValues: string[], dataTypes: string[]}}
   */
  this.getInputParams=function(){
    var result={
      "name": this.jqElements.nameInput.val(),
      "mediaType": "csv",
      "dbType": this.jqElements.databaseTypeInput.val(),
      "separator": this.jqElements.delimiterInput.val(),
      "encoding": this.jqElements.encodingInput.val(),
      "quotesChar": this.jqElements.enclosureInput.val(),
      "escapeChar": this.jqElements.escapeCharacterInput.val(),
      "locale": this.jqElements.localeInput.val(),
      nullValues: [this.jqElements.nullValueInput.val()],
      columnNames: columnNames,
      dataTypes: columnDataTypes
    };
    if (this.fileCompression){
      result['compression']=this.fileCompression;
    }
    return result;
  };

  /**
   * Funkce pro upload ukázkových dat
   */
  this.uploadPreviewData=function(){
    //TODO vyřešení komprese v případě, kdy není zipSupport na serveru
    //region upload ukázkových dat
    var fileReader=new FileReader();
    fileReader.onload=function(){
      var url='';
      if (self.fileCompression && self.zipSupport){
        url=self.uploadPreviewDataUrl.replace('__COMPRESSION__',self.fileCompression);
      }else{
        url=self.uploadPreviewDataUrl.replace('__COMPRESSION__','');
      }
      jQuery.ajax(url,{
        type: 'post',
        data: fileReader.result,
        //contentType: 'text/plain',
        dataType: 'json',
        processData:false,
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
      alert('Requested file is not readable!');
    };
    var file=this.fileInputElement.files[0];
    this.processFileName(file.name);
    fileReader.readAsArrayBuffer(file.slice(0,Math.min(UPLOAD_PREVIEW_BYTES,file.size)));
    //endregion
  };


  //region init
  /**
   * Kompletní spuštění inicializačních funkcí této JS komponenty
   */
  this.init=function(){
    initUploadFormActions();
    initUploadConfigFormActions();
    initColumnsConfigFormActions();
    initFileUploader();
    //zobrazíme první část uploadu
    self.showUploadFormBlock();
  };

  /**
   * Funkce pro inicializaci nahrávacího formuláře
   */
  var initUploadFormActions=function(){
    var form=self.jqElements.uploadFormBlock.find('form');
    form.addClass('ajax');
    form.submit(function(e){
      e.preventDefault();
      e.stopPropagation();
      if (Nette.validateForm(this)){
        //načteme preview, posléze dojde k zobrazení konfigurace importu
        self.reloadPreview(true);
      }
    });
  };

  /**
   * Funkce inicializující formulář pro konfiguraci parametrů uploadu
   */
  var initUploadConfigFormActions=function(){
    var form=self.jqElements.uploadConfigBlock.find('form');
    var changeFunction=function(){
      //změna hodnoty inputu (jeho opuštění)
      if (!$(this).hasClass(LiveForm.options.controlErrorClass)){
        if ($(this).prop('data-old-value')!=$(this).val()){
          $(this).prop('data-old-value',$(this).val());
          self.reloadPreview();
        }
      }
    };
    form.find('select').change(changeFunction);
    form.find('input[type="text"]').blur(changeFunction);
    form.submit(function(e){
      e.preventDefault();
      e.stopPropagation();
      if(Nette.validateForm(this)){
        //zobrazíme konfiguraci jednotlivých sloupců...
        self.showColumnsConfigBlock();
      }
    });
    form.addClass('ajax');
  };

  /**
   * Funkce pro inicializaci formuláře pro konfiguraci sloupců
   */
  var initColumnsConfigFormActions=function(){
    var form=self.jqElements.uploadColumnsBlock.find('form');
    form.addClass('ajax');
    /**
     * Funkce pro validaci unikátnosti zadaných jmen sloupců
     * @return {boolean}
     */
    Nette.validators.UniqueNamesValidator=function(elem, arg, value){
      var namesArr=[];
      $('#'+elem.form.id+' input.columnName').each(function(){
        if (elem.id==$(this).attr('id')){return;}
        var name=$(this).val();
        namesArr[name]=name;
      });
      //console.log(namesArr);
      return !namesArr.hasOwnProperty(value);
    };

    //submit akce
    form.submit(function(e){
      e.preventDefault();
      e.stopPropagation();
      Nette.validateForm(this);

      self.submitAllData();
    })
  };

  /**
   * Funkce pro inicializaci file uploaderu
   */
  var initFileUploader=function(){
    fileUploader=new FileUploader({
      apiKey:self.apiKey,
      inputElementId:self.fileInputElement.id,
      onShowMessage: function(message, type){
        showMessage(message, type);
      }
    });
    self.jqElements.uploadProgress.find('a.stopButton').click(function(){
      $(this).hide();
      showMessage('Upload stopped.','warn');
      fileUploader.stopUpload();
    });
  };
  //endregion init
};


$(document).ready(function(){
  //inicializace celé aplikace
  var dataUpload=new DataUpload();
  var uploadFormBlock=$('#uploadFormBlock');
  var uploadConfigBlock=$('#uploadConfigBlock');
  var uploadProgressBlock=$('#uploadProgressBlock');
  dataUpload.fileInputElement=uploadFormBlock.find('form input[type="file"]').get(0);
  $('form').addClass('ajax');

  var uploadProgress=uploadProgressBlock.find('.progress');
  dataUpload.jqElements={
    uploadFormBlock:    uploadFormBlock,

    uploadConfigBlock:  uploadConfigBlock,
    uploadConfigPreviewBlock: uploadConfigBlock.find('#uploadConfigPreviewBlock'),

    uploadColumnsBlock: $('#uploadColumnsBlock'),
    uploadColumnsListBlock: $('#uploadColumnsListBlock'),

    uploadProgressBlock:uploadProgressBlock,
    uploadProgress: uploadProgress,
    uploadProgressBar:  uploadProgress.find('.progressBar'),
    uploadProgressMessage: uploadProgressBlock.find('.message'),

    databaseTypeInput: uploadFormBlock.find('[name="dbType"]'),
    nameInput: uploadConfigBlock.find('[name="name"]'),
    escapeCharacterInput: uploadConfigBlock.find('[name="escape"]'),
    delimiterInput: uploadConfigBlock.find('[name="separator"]'),
    nullValueInput: uploadConfigBlock.find('[name="nullValue"]'),
    encodingInput: uploadConfigBlock.find('[name="encoding"]'),
    enclosureInput: uploadConfigBlock.find('[name="enclosure"]'),
    localeInput: uploadConfigBlock.find('[name="locale"]'),

    flashMessages: $('.flash')//blok klasických flash zpráv v rámci Nette
  };

  dataUpload.dataServiceUrlsByDbTypes=dataServiceUrlsByDbTypes;
  dataUpload.apiKey=apiKey;
  dataUpload.previewUrl=previewUrl;
  dataUpload.uploadPreviewDataUrl=uploadPreviewDataUrl;
  dataUpload.zipSupport=zipSupport;
  dataUpload.uploadFinishUrl=uploadFinishUrl;
  dataUpload.init();

  //inicializace nápovědy pro jednotlivé typy DB
  var dbTypeSelect=$('#frm-uploadForm-dbType');
  dbTypeSelect.change(function(){
    $('#frm-uploadForm-dbType-hint').text(getDatabaseHint(dbTypeSelect.val()));
  });
  var hint=$('<div id="frm-uploadForm-dbType-hint" class="info-message"></div>');
  hint.text(getDatabaseHint(dbTypeSelect.val()));
  dbTypeSelect.after(hint);
});


/**
 * Funkce pro vytvoření SEO URL ze zadaného řetězce
 * @param str
 * @returns string
 */
function seoUrl(str) {
  str = str.toLowerCase();
  str = strtr(str,String.fromCharCode(283,353,269,345,382,253,225,237,233,357,367,250,243,271,328,318,314),String.fromCharCode(101,115,99,114,122,121,97,105,101,116,117,117,111,100,110,108,108));
  str = str.replace(/[^0-9A-Za-z]{1,}?/g, ' ').replace(/^\s*|\s*$/g,"").replace(/[\s]+/g, '-');

  return str;
}

/**
 * Funkce strtr odpovida teto funkci z PHP
 */
function strtr(s, from, to) {
  var out = "";
  // slow but simple :^)
  top:
    for(var i=0; i < s.length; i++) {
      for(var j=0; j < from.length; j++) {
        if(s.charAt(i) == from.charAt(j)) {
          out += to.charAt(j);
          continue top;
        }
      }
      out += s.charAt(i);
    }
  return out;
}

/**
 * Funkce pro rozbalení části ZIP archívu
 * @param file - soubor z input[type='file']
 * @param contentSize : int
 * @param resultFunction - funkce, která se má spustit po dokončení
 */
function getPartOfZipFile(file, contentSize, resultFunction){
  var fileReader=new FileReader();
  fileReader.readAsArrayBuffer(file.slice());
  fileReader.onload=function(){
    var zipArchive=new JSZip();
    zipArchive.load(fileReader.result);
    console.log(zipArchive);
    var firstFile=null;
    for(var name in zipArchive.files){
      //noinspection JSUnfilteredForInLoop
      var file=zipArchive.files[name];
      if(!file.dir){
        firstFile=file;
        break;
      }
    }
    resultFunction(firstFile.asUint8Array().slice(0, contentSize));
  }
}

function getDatabaseHint(dbType){
  switch (dbType){
    case 'mysql':return 'MySQL database, suitable for usage with LISp-Miner or R backend';
    case 'limited':return 'Recommended database type, suitable for most datasets. Works with R backend.';
    case 'unlimited':return 'For really big datasets only, in this demo installation, simultaneous work of more users is disabled. Works with Hive/Sparql backend.';
  }
}