/**
 * Skripty pro upload dat
 */

'use strict';



var apiKey='9bafafb9bb0fff55bf618047f526d40fc67fb3bbecfced';
var dataServiceUrl='http://br-dev.lmcloud.vse.cz/easyminer-data/api/v1/';
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