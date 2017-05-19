'use strict';

/**
 * @class FileUploader - javascriptová komponenta pro upload dat do datové služby
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @param {Object} [params={}]
 * @constructor
 */
var FileUploader=function(params){
  var apiKey='';
  var dataServiceUrl='';
  var inputElementId='';

  /**
   * @type {number} maxFileSize=100MB - maximal size of files for upload
   */
  var maxFileSize=1024*1024*1000;
  /**
   * @type {int} chunkSize=500kB - size of one upload chunk
   */
  var chunkSize=1024*500;
  /*interní pracovní proměnné*/
  var uploadInProgress=false;
  var file=null;
  var lastUploadedChunkId=0;
  var uploadRequestUrl="";
  var fileReader=null;
  var self=this;
  /**
   * @type {string} uploadId - ID of the given upload
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



  const SLOW_DOWN_INTERVAL=500;//length of time interval for upload slowdown (in miliseconds)
  const CODE_SLOW_DOWN=429;
  const CODE_CONTINUE=202;
  const CODE_OK=200;

  /**
   * Function for start of the upload
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
        //upload initialization was successfull => save the ID of upload and start the sending...
        uploadId=result;
        initFileReader();
        uploadInProgress=true;
        self.onProgressUpdate();
        sendChunk();
      },
      error: function(xhr, status, error){
        //initialization failed - display error info and cancel the sending...
        showMessage("Upload failed: "+error);
        self.stopUpload();
      }
    });
  };

  var initFileReader = function(){
    fileReader=new FileReader();
    fileReader.onload=function(){
      //data loaded -> send them... (upload them)
      //noinspection JSUnusedGlobalSymbols
      jQuery.ajax(prepareUrl(uploadId),{
        type: 'post',
        data: fileReader.result,
        //contentType: 'text/plain',
        processData:false,
        success: function(result,status,xhr){
          if (xhr.status==CODE_CONTINUE){
            //we should continue, display the state message
            showMessage(result);
          }
          //response received
          self.onProgressUpdate();
          lastUploadedChunkId++;
          sendChunk();
        },
        error: function(xhr, status, error){
          //error occurred - display info about the error and cancel the sendind...
          var errorCode=xhr.status;
          if (errorCode==CODE_SLOW_DOWN){
            //slowdown request received - slow down the sending (upload)
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
      //action called in case of file read error
      showMessage("File read failed!","error");
      self.stopUpload();
    };
  };

  /**
   * Function for sending of next part of the file
   */
  var sendChunk=function(){
    //calculation of the start and end of the chunk for upload
    var dataStart=lastUploadedChunkId*chunkSize;
    var dataEnd=Math.min(dataStart+chunkSize,file.size);
    if (dataStart>=dataEnd){
      //complete file was send, send the final message
      sendLastChunk();
      return;
    }
    self.onProgressUpdate();
    //load data (event for sending is already attached to the instance of object FileReader)
    fileReader.readAsArrayBuffer(file.slice(dataStart,dataEnd));
  };

  /**
   * Function for sending of the last message - finish the upload
   */
  var sendLastChunk=function(){
    //noinspection JSUnusedGlobalSymbols
    jQuery.ajax(prepareUrl(uploadId),{
      type: 'post',
      data: "",
      contentType: 'text/plain',
      success: function(result,status,xhr){
        //response received
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
        //error occured - display info about the error and cancel the sending
        if (xhr.status==CODE_SLOW_DOWN){
          //slowdown request received - slow down the sending (upload)
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
   * Function for starting of the file upload
   * @param {string} requestUrl
   * @param {object|[]} uploadFileParams - params required for the upload start
   * @returns {boolean}
   */
  this.startUpload=function(requestUrl,uploadFileParams){
    //if the browser does not support HTML5 upload, try to send the form classically
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
    //start the upload of the file
    if (requestUrl.substr(0,1)=='/'){
      uploadRequestUrl=requestUrl.substr(1);
    }else{
      uploadRequestUrl=requestUrl;
    }
    start(uploadFileParams);
  };

  /**
   * Function returning the representation of actually uploaded parts
   * @returns {number}
   */
  this.getProgressState=function(){
    if (file==null){
      return 0;
    }
    return (lastUploadedChunkId*chunkSize)/file.size;
  };

  /**
   * Function for stopping of the running upload
   */
  this.stopUpload=function(){
    uploadInProgress=false;
    lastUploadedChunkId=0;
    file=null;
    self.onUploadStop();
  };

  /**
   * Function for check, if the browser support required HTML5 javascript objects
   */
  this.checkBrowserSupport=function(){
    return window.File && window.FileReader && window.FileList && window.Blob;
  };

  /**
   * Function for preparation of the URL for requests to the data service
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
   * Function for display of the appropriate message
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
   * Init functions for setting of required params
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
