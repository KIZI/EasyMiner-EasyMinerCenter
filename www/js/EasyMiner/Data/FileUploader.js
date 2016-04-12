'use strict';

/**
 * @class FileUploader - javascriptová komponenta pro upload dat do datové služby
 * @author Stanislav Vojíř
 * @param {Object} [params={}]
 * @constructor
 */
var FileUploader=function(params){
  var apiKey='';
  var dataServiceUrl='';
  var inputElementId='';

  /**
   * @type {number} maxFileSize=100MB Maximální velikost uploadovaných souborů
   */
  var maxFileSize=1024*1024*100;
  /**
   * @type {int} chunkSize=300kB - Velikost jednoho uploadovaného bloku
   */
  var chunkSize=1024*300;
  /*interní pracovní proměnné*/
  var uploadInProgress=false;
  var file=null;
  var lastUploadedChunkId=0;
  var uploadRequestUrl="";
  var fileReader=null;
  var self=this;
  /**
   * @type {string} uploadId - ID daného uploadu
   */
  var uploadId="";
  /**
   * Custom function triggered on progress update
   */
  this.onProgressUpdate=function(){};
  /**
   * Custom function triggered on upload start
   */
  this.onUploadStart=function(){};
  /**
   * Custom function triggered on upload finished
   */
  this.onUploadFinished=function(){};
  /**
   * Custom function triggered on upload stop
   */
  this.onUploadStop=function(){};
  /**
   * Custom function triggered after last file send.
   */
  this.onFileSent=function(){};
  /**
   * Custom function triggered on message alert
   */
  this.onShowMessage=function(){};



  const SLOW_DOWN_INTERVAL=500;//interval pro zpomalení uploadu (v milisekundách)
  const CODE_SLOW_DOWN=429;
  const CODE_CONTINUE=202;
  const CODE_OK=200;

  /**
   * Funkce pro začátek uploadu
   * @param {object} uploadFileParams
   * @returns {boolean}
   */
  var start=function(uploadFileParams){
    var ajaxData=JSON.stringify(uploadFileParams);
    var ajaxUrl=prepareUrl('start');
    //noinspection JSUnusedGlobalSymbols
    jQuery.ajax(ajaxUrl,{
      type: 'post',
      data: ajaxData,
      contentType: 'application/json; charset=utf-8',
      success: function(result){
        //došlo k úspěšné inicializaci -> uložíme si ID uploadu a zahájíme posílání...
        uploadId=result;
        initFileReader();
        uploadInProgress=true;
        self.onProgressUpdate();
        sendChunk();
      },
      error: function(xhr, status, error){
        //došlo k chybě - zobrazíme info o chybě a následně stornujeme posílání
        showMessage("Upload failed: "+error);
        self.stopUpload();
      }
    });
  };

  var initFileReader = function(){
    fileReader=new FileReader();
    fileReader.onload=function(){
      //data načtena -> odešleme je
      //noinspection JSUnusedGlobalSymbols
      jQuery.ajax(prepareUrl(uploadId),{
        type: 'post',
        data: fileReader.result,
        contentType: 'text/plain',
        success: function(result,status,xhr){
          if (xhr.status==CODE_CONTINUE){
            //pokud máme pokračovat, zobrazíme stavovou zprávu
            showMessage(result);
          }
          //došlo k vrácení odpovědi
          self.onProgressUpdate();
          lastUploadedChunkId++;
          sendChunk();
        },
        error: function(xhr, status, error){
          //došlo k chybě - zobrazíme info o chybě a následně stornujeme posílání
          var errorCode=xhr.status;
          if (errorCode==CODE_SLOW_DOWN){
            //byl obdržen požadavek na zpomalení uploadu
            setTimeout(function(){
              sendChunk();
            },SLOW_DOWN_INTERVAL);
          }else{
            showMessage("Upload failed: "+error);
            self.stopUpload();
          }
        }
      });
    };
    fileReader.onerror=function(){
      //akce volaná při chybě čtení souboru
      showMessage("File read failed!","error");
      self.stopUpload();
    };
  };

  /**
   * Funkce pro odeslání další části souboru
   */
  var sendChunk=function(){
    //určení začátku a konce daného uploadu
    var dataStart=lastUploadedChunkId*chunkSize;
    var dataEnd=Math.min(dataStart+chunkSize,file.size);
    if (dataStart>=dataEnd){
      //pokud už byl odeslán celý soubor, pošleme ukončovací zprávu
      sendLastChunk();
      return;
    }
    self.onProgressUpdate();
    //načteme data (událost pro odeslání již je připojena k instanci objektu FileReader)
    fileReader.readAsBinaryString(file.slice(dataStart,dataEnd));
  };

  /**
   * Funkce pro odeslání poslední zprávy - pro ukončení uploadu
   */
  var sendLastChunk=function(){
    //noinspection JSUnusedGlobalSymbols
    jQuery.ajax(prepareUrl(uploadId),{
      type: 'post',
      data: "",
      contentType: 'text/plain',
      success: function(result,status,xhr){
        //došlo k vrácení odpovědi
        if (xhr.status==CODE_OK){
          showMessage("File uploaded successfully, data preparation in progress...");
          self.onFileSent();
          self.onUploadFinished(result);
        }else{
          if(xhr.status==CODE_CONTINUE){
            self.onFileSent();
            showMessage(result);
          }
          setTimeout(function(){
            sendLastChunk();
          },SLOW_DOWN_INTERVAL);
        }
      },
      error: function(xhr, status, error){
        //došlo k chybě - zobrazíme info o chybě a následně stornujeme posílání
        if (xhr.status==CODE_SLOW_DOWN){
          //byl obdržen požadavek na zpomalení uploadu
          setTimeout(function(){
            sendLastChunk();
          },SLOW_DOWN_INTERVAL);
        }else{
          showMessage("Upload failed: "+error);
          self.stopUpload();
        }
      }
    });
  };

  /**
   * Funkce pro zahájení uploadu souboru
   * @param {string} requestUrl
   * @param {object|[]} uploadFileParams parametry, které je nutné zaslat při požadavku na zahájení uploadu
   * @returns {boolean}
   */
  this.startUpload=function(requestUrl,uploadFileParams){
    //pokud prohlížeč metody nepodporuje, zkusíme odeslat formulář klasicky...
    if (!this.checkBrowserSupport()){
      showMessage("This browser does not support this component.","error");
      return false;
    }
    if (uploadInProgress) {
      showMessage("Upload is currently in progress...", 'error');
      return false;
    }
    var inputElement=document.getElementById(inputElementId);
    if (inputElement.files.length!=1){
      showMessage("You have to select one file for upload!","error");
      return false;
    }
    file=inputElement.files[0];
    if (file.size>maxFileSize){
      showMessage("Uploaded file is too big!","error");
      return false;
    }
    self.onUploadStart();
    //začneme nahrávat souboru...
    if (requestUrl.substr(0,1)=='/'){
      uploadRequestUrl=requestUrl.substr(1);
    }else{
      uploadRequestUrl=requestUrl;
    }
    start(uploadFileParams);
  };

  /**
   * Funkce vracející vyjádření aktuálně naimportovaných částí
   * @returns {number}
   */
  this.getProgressState=function(){
    if (file==null){
      return 0;
    }
    return (lastUploadedChunkId*chunkSize)/file.size;
  };

  /**
   * Funkce pro zastavení běžícího uploadu
   */
  this.stopUpload=function(){
    uploadInProgress=false;
    lastUploadedChunkId=0;
    file=null;
    self.onUploadStop();
  };

  /**
   * Funkce pro kontrolu, jestli browser podporuje potřebné javascriptové objekty
   */
  this.checkBrowserSupport=function(){
    return window.File && window.FileReader && window.FileList && window.Blob;
  };

  /**
   * Funkce pro připravení URL adresy pro odeslání požadavku
   * @param {string} action
   * @returns {string}
   */
  var prepareUrl=function(action){
    if (action.substr(0,1)=='/'){
      action=action.substr(1);
    }
    return dataServiceUrl+uploadRequestUrl+'/'+action+'?apiKey='+apiKey;
  };

  /**
   * Funkce pro zobrazení příslušné zprávy
   * @param {string} text
   * @param {string} [type="info"]
   */
  var showMessage = function (text, type) {
    if (type == undefined) {
      type = "info";
    }
    //log the message into console
    if (type=='error' || type=='warning'){
      console.warn("Uploader: " + text);
    }else{
      console.log("Uploader: " + text);
    }
    //execute binded function
    self.onShowMessage(text, type);
  };

  this.setDataServiceUrl=function(url){
    dataServiceUrl=url;
    if(dataServiceUrl.substring(dataServiceUrl.length-1)!='/'){
      dataServiceUrl+='/';
    }
  };

  /**
   * Inicializační funkce pro nastavení potřebných parametrů
   * @param {object} [params]
   */
  this.init=function(params){
    if (params.dataServiceUrl!=null){
      this.setDataServiceUrl(params.dataServiceUrl);
    }
    if (params.apiKey!=null){
      apiKey=params.apiKey;
    }
    if (params.inputElementId!=null){
      inputElementId=params.inputElementId;
    }
    if (params.maxFileSize!=null && params.maxFileSize>0){
      maxFileSize=params.maxFileSize;
    }
    //custom functions
    if (params.onProgressUpdate!=null){
      this.onProgressUpdate=params.onProgressUpdate;
    }
    if (params.onUploadFinished!=null){
      this.onUploadFinished=params.onUploadFinished;
    }
    if (params.onUploadStart!=null){
      this.onUploadStart=params.onUploadStart;
    }
    if (params.onUploadStop!=null){
      this.onUploadStop=params.onUploadStop;
    }
    if (params.onShowMessage!=null){
      this.onShowMessage=params.onShowMessage;
    }
  };
  if (params!=undefined){
    this.init(params);
  }
};
