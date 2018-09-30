/**
 * Scripts for data upload in the UI
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

'use strict';

/**
 * @class DataUpload
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @constructor
 */
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
    allowLongNamesInput: null,
    databaseTypeInput: null,
    importTypeInput: null,
    escapeCharacterInput: null,
    delimiterInput: null,
    nullValueInput: null,
    encodingInput: null,
    enclosureInput: null,
    localeInput: null
  };
  this.dataServicesConfigByDbTypes=null;
  this.apiKey=null;
  this.previewUrl=null;
  this.uploadPreviewDataUrl=null;
  this.zipSupport=false;
  this.uploadFinishUrl=null;
  this.fileCompression='';

  const LONG_NAMES_MAX_LENGTH=255;
  const SHORT_NAMES_MAX_LENGTH=40;

  /**
   * @type {FileUploader}
   */
  var fileUploader;
  /**
   * Size of uploaded data for data preview
   * @type {number}
   */
  const UPLOAD_PREVIEW_BYTES=100000;
  /**
   * Identification of uploaded file for data preview
   * @type {string}
   */
  var previewFileName='';
  var columnNames=[];
  var columnDataTypes=[];
  var previewRows=[];
  var self=this;

  /**
   * Function returning the URL for data preview
   * @returns {string}
   */
  var getPreviewUrl=function(reloadParams){
    var requireSafeNames=(self.jqElements.allowLongNamesInput.val()==1?0:1);
    var result=self.previewUrl;
    var inputParams=self.getInputParams();
    result=result
      .replace('__FILE__',encodeURIComponent(previewFileName))
      .replace('__ENCODING__',encodeURIComponent(inputParams.encoding))
      .replace('__ENCLOSURE__',encodeURIComponent(inputParams.quotesChar))
      .replace('__ESCAPE_CHARACTER__',encodeURIComponent(inputParams.escapeChar))
      .replace('__NULL_VALUE__',encodeURIComponent(inputParams.nullValues[0]))
      .replace('__LOCALE__',encodeURIComponent(inputParams.locale))
      .replace('__REQUIRE_SAFE_NAMES__',requireSafeNames);
    //if we need to reload params, we will not send the following params
    if (reloadParams==true){
      result=result.replace('__DELIMITER__','');
    }else{
      result=result.replace('__DELIMITER__',encodeURIComponent(inputParams.separator));
    }
    return result;
  };

  /**
   * Function for display of the block with upload form
   */
  this.showUploadFormBlock=function(){
    this.jqElements.uploadConfigBlock.hide();
    this.jqElements.uploadColumnsBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadFormBlock.show();
  };
  /**
   * Function for initialization of the upload configuration
   */
  this.showUploadConfigBlock=function(){
    //hide possible flash messages
    this.jqElements.flashMessages.hide();
    //generate the default name of the uploaded datasource
    this.processFileName(this.fileInputElement.files[0].name);
    //display necessary blocks
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadColumnsBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.show();
  };

  /**
   * Function for processing the name of the file for upload - configuration of compression and the name of data table
   * @param filename : string
   */
  this.processFileName=function(filename){
    var lastDotPosition=filename.lastIndexOf('.');
    var name=filename.substring(0,lastDotPosition);
    var ext =filename.substring(lastDotPosition+1);
    //process the file name for the name of a table in database
    var seoName=seoUrl(name);
    seoName=seoName.replace(/-/g,'_');//replace - with _
    ext=ext.toLowerCase();
    //set the gained values to inputs and to a variable with info about compression
    if (this.jqElements.allowLongNamesInput.val()=='1'){
      this.jqElements.nameInput.val(name);
    }else{
      this.jqElements.nameInput.val(seoName);
    }

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
   * Function for generation of a form for configuration of data fields (data columns) and display of the generated form
   */
  this.showColumnsConfigBlock=function(){
    var columnNamesValidationAttributes='required';
    if (this.jqElements.allowLongNamesInput.val()=='1'){
      //check for the long names of data fields (with long names support)
      columnNamesValidationAttributes+='maxlength="'+LONG_NAMES_MAX_LENGTH+'" data-nette-rules=\'[{"op":":filled","msg":"This field is required."},{"op":":maxLength","msg":"Max length of the column name is '+LONG_NAMES_MAX_LENGTH+' characters!","arg":'+LONG_NAMES_MAX_LENGTH+'},{"op":":UniqueNamesValidator","msg":"This name is already used."}]\' title="Attribute name is required."';
    }else{
      //check for the short names of data fields (without long names support)
      columnNamesValidationAttributes+='maxlength="'+SHORT_NAMES_MAX_LENGTH+'" data-nette-rules=\'[{"op":":filled","msg":"This field is required."},{"op":":UniqueNamesValidator","msg":"This name is already used."},{"op":":maxLength","msg":"Max length of the column name is '+SHORT_NAMES_MAX_LENGTH+' characters!","arg":'+SHORT_NAMES_MAX_LENGTH+'},{"op":":pattern","msg":"Column name can contain only letters, numbers and _ and has start with a letter.","arg":"[a-zA-Z]{1}\\\\w*"}]\' pattern="[a-zA-Z]{1}\\w*" title="Column name can contain only letters, numbers and _ and has start with a letter."';
    }

    //prepare items of the appropriate form
    var listBlock=this.jqElements.uploadColumnsListBlock;
    var listBlockTable=$('<table><tr><th>'+'Column name'+'</th><th>'+'Data type'+'</th><th>'+'Values from first rows...'+'</th></tr></table>');
    for (var i in columnNames){
      //single data column details
      var columnDetailsTr=$('<tr></tr>');
      var nameInput=$('<input type="text" id="column_'+i+'_name" '+columnNamesValidationAttributes+' />').val(columnNames[i]).addClass('columnName');
      columnDetailsTr.append($('<td></td>').html(nameInput));
      var dataTypeSelect=$('<select id="column_'+i+'_type"><option value="nominal">nominal [ABC]</option><option value="numeric">numerical [123]</option><option value="null">--ignore--</option></select>');
      dataTypeSelect.val(columnDataTypes[i]);
      columnDetailsTr.append($('<td></td>').html(dataTypeSelect));
      var valuesTd=$('<td class="values"></td>');
      //preview of values
      var previewedRows=0;
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
    listBlock.html(listBlockTable);//set the table to the appropriate content block
    //display required blocks
    this.jqElements.uploadFormBlock.hide();
    this.jqElements.uploadProgressBlock.hide();
    this.jqElements.uploadConfigBlock.hide();
    this.jqElements.uploadColumnsBlock.show();

    Nette.initForm(self.jqElements.uploadColumnsBlock.find('form').get(0));
  };

  /**
   * Function for upload of a part of data file for data preview
   */
  this.reloadPreview=function(reloadParams){
    this.jqElements.allowLongNamesInput.val(this.dataServicesConfigByDbTypes[this.jqElements.databaseTypeInput.val()]['allowLongNames']?'1':'0');
    if (previewFileName==""){
      this.uploadPreviewData();
      return;
    }

    $.getJSON(getPreviewUrl(reloadParams),function(data){
      //set variables
      columnNames=data['columnNames'];
      columnDataTypes=data['dataTypes'];
      previewRows=data['data'];
      //set values of form elements using the data evaluation on the server
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
      //prepare a preview table
      var previewTable=$('<table></table>');
      var columnsCount=0;
      //column names
      var tr=$('<tr></tr>');
      for (var columnI in columnNames){
        var th=$('<th></th>');
        th.text(columnNames[columnI]);
        tr.append(th);
        columnsCount++;
      }
      previewTable.append(tr);
      //values of individual rows
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
   * Function for upload of the full file
   */
  this.submitAllData=function(){
    //initialize file uploaderu
    fileUploader.setDataServiceUrl(this.dataServicesConfigByDbTypes[self.jqElements.databaseTypeInput.val()].url);//nastavení URL dle zvoleného typu databáze
    fileUploader.onUploadStart=function(){
      //display the upload block and start the upload
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
      //remove the event functions from uploader
      fileUploader.onShowMessage=function(){};
      fileUploader.onProgressUpdate=function(){};
      //hide progress bar
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
   * Function for reload of the list of required column names and its data types
   */
  var updateColumnNamesAndTypesFromConfigForm=function(){
    //load data from form
    var columnsData=[];
    self.jqElements.uploadColumnsListBlock.find('input[type="text"]').each(function(){
      var thisItem=$(this);
      var nameArr = thisItem.attr('id').split('_');
      if (nameArr[0]=='column' && nameArr.hasOwnProperty('2') && nameArr[2]=='name'){
        columnsData.push([thisItem.val(), $('#column_'+nameArr[1]+'_type').val()]);
      }
    });

    //prepare array with column names and data types for upload
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
   * Function for sending of the "final" request
   * @param dataServiceResponse : object
   */
  this.sendFinalRequest=function(dataServiceResponse){
    //send request for finishing of the data upload (create datasource)
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
        //error occurred - display info about the error and cancel the sending (uploading) of file
        showMessage("Upload failed: "+error, 'error');
      }
    });
  };

  /**
   * Function for displaying of a info message
   * @param message : string
   * @param type : string
   */
  var showMessage=function(message, type){
    var messageElement=$('<div class="'+type+'"></div>').html(message);
    self.jqElements.uploadProgressMessage.html(messageElement);
  };

  /**
   * Function for building of params for data upload
   * @returns {{name: string, mediaType: string, dbType: string, separator: string, encoding: string, quotesChar: string, escapeChar: string, locale: string, nullValues: string[], dataTypes: string[]}}
   */
  this.getInputParams=function(){
    var result={
      "name": this.jqElements.nameInput.val(),
      "mediaType": this.jqElements.importTypeInput.val(),
      //TODO podpora uploadu RDF - issue kizi/EasyMiner-EasyMinerCenter#174 (neměly by být vyplněny další parametry)
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
   * Function for upload of data for data preview
   */
  this.uploadPreviewData=function(){
    //TODO vyřešení komprese v případě, kdy není zipSupport na serveru (solve the support for zip without support on the server side)
    //region upload data for preview
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
          //file uploaded - display data preview...
          previewFileName=result.file;
          self.reloadPreview(true);
        },
        error: function(xhr, status, error){
          //FIXME error message
        }
      });
    };
    fileReader.onerror=function(){
      alert('Requested file is not readable!');
    };
    var file=this.fileInputElement.files[0];
    this.processFileName(file.name);
    fileReader.readAsArrayBuffer(file.slice(0,Math.min(UPLOAD_PREVIEW_BYTES,file.size)));
    //endregion upload data for preview
  };


  //region init
  /**
   * Run all initialization functions of this JavaScript components
   */
  this.init=function(){
    initUploadFormActions();
    initUploadConfigFormActions();
    initColumnsConfigFormActions();
    initFileUploader();
    //display first part of upload
    self.showUploadFormBlock();
  };

  /**
   * Function for initialization of the upload form
   */
  var initUploadFormActions=function(){
    var form=self.jqElements.uploadFormBlock.find('form');
    form.addClass('ajax');
    self.jqElements.importTypeInput.change(function(){
      //in case of data type change, run the validation on the form field for selection of the database type
      Nette.validateControl(self.jqElements.databaseTypeInput);
    });
    form.find('input[name="cancel"]').click(function(){
      form.attr('data-cancel','ok');
    });
    form.submit(function(e){
      if (form.attr('data-cancel')=='ok'){return;}
      e.preventDefault();
      e.stopPropagation();
      if (Nette.validateForm(this)){
        //TODO tady bude potřeba úprava pro podporu uploadu RDF dat
        //reload preview, later the import config will be displayed
        self.reloadPreview(true);
      }
    });
  };

  /**
   * Function for initialization of form for configuration of upload params
   */
  var initUploadConfigFormActions=function(){
    var form=self.jqElements.uploadConfigBlock.find('form');
    var changeFunction=function(){
      //input value change (blur of the input)
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
        //display config of individual columns
        self.showColumnsConfigBlock();
      }
    });
    form.addClass('ajax');
  };

  /**
   * Function for initialization of form for configuration of columns
   */
  var initColumnsConfigFormActions=function(){
    var form=self.jqElements.uploadColumnsBlock.find('form');
    form.addClass('ajax');
    /**
     * Function for validation of uniqueness of column names
     * @return {boolean}
     */
    Nette.validators.UniqueNamesValidator=function(elem, arg, value){
      var namesArr={};
      $('#'+elem.form.id+' input.columnName').each(function(){
        if (elem.id==$(this).attr('id')){return;}
        var name=$(this).val();
        namesArr[name]=name;
      });
      return !namesArr.hasOwnProperty(value);
    };

    //submit action
    form.submit(function(e){
      e.preventDefault();
      e.stopPropagation();
      Nette.validateForm(this);

      self.submitAllData();
    })
  };

  /**
   * Function for initialization of file uploader
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
  //initialization of the full application
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
    importTypeInput: uploadFormBlock.find('[name="importType"]'),
    nameInput: uploadConfigBlock.find('[name="name"]'),
    allowLongNamesInput: uploadConfigBlock.find('[name="allowLongNames"]'),
    escapeCharacterInput: uploadConfigBlock.find('[name="escape"]'),
    delimiterInput: uploadConfigBlock.find('[name="separator"]'),
    nullValueInput: uploadConfigBlock.find('[name="nullValue"]'),
    encodingInput: uploadConfigBlock.find('[name="encoding"]'),
    enclosureInput: uploadConfigBlock.find('[name="enclosure"]'),
    localeInput: uploadConfigBlock.find('[name="locale"]'),

    flashMessages: $('.flash')//blok klasických flash zpráv v rámci Nette
  };

  dataUpload.dataServicesConfigByDbTypes=dataServicesConfigByDbTypes;
  dataUpload.apiKey=apiKey;
  dataUpload.previewUrl=previewUrl;
  dataUpload.uploadPreviewDataUrl=uploadPreviewDataUrl;
  dataUpload.zipSupport=zipSupport;
  dataUpload.uploadFinishUrl=uploadFinishUrl;
  dataUpload.init();

  //initialize the help for individual types of databases
  var dbTypeSelect=$('#frm-uploadForm-dbType');
  dbTypeSelect.change(function(){
    $('#frm-uploadForm-dbType-hint').text(getDatabaseHint(dbTypeSelect.val()));
  });
  var hint=$('<div id="frm-uploadForm-dbType-hint" class="info-message"></div>');
  hint.text(getDatabaseHint(dbTypeSelect.val()));
  dbTypeSelect.after(hint);
});


/**
 * Function for generating of SEO URL from the given string
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
 * Alternative for the function STRTR from PHP
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
 * Function for decompression of a part of ZIP archive file
 * @param file - soubor z input[type='file']
 * @param contentSize : int
 * @param resultFunction - function to run after decompression will be completed
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

/**
 * Function returning hints (help texts) for individual types of databases
 * @param dbType : string
 * @returns string
 */
function getDatabaseHint(dbType){
  switch (dbType){
    case 'mysql':return 'MySQL database, suitable for usage with LISp-Miner or R backend';
    case 'limited':return 'Recommended database type, suitable for most datasets. Works with R backend.';
    case 'unlimited':return 'For really big datasets only, in this demo installation, simultaneous work of more users is disabled. Works with Hive/Sparql backend.';
  }
}