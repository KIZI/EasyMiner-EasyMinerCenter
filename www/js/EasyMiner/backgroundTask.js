/**
 * @class BackgroundTask - javascriptová komponenta pro spouštění background requestů pro dlouhotrvající úlohy
 * @author Stanislav Vojíř
 * @param {Object} [params={}]
 * @constructor
 */
var BackgroundTask = function(params){
  var url=params.url;
  var sleepInterval = params.sleep ? params.sleep : 500;
  var messageTarget = params.messageTarget;
  var self=this;

  /**
   * @param url : string
   */
  var sendTaskRequest = function(url){
    jQuery.getJSON(url, function(data){
      $(messageTarget).html(data.message);
      if (data.redirect!=''){
        location.href=data.redirect;
      }else{
        setTimeout(sendTaskRequest, sleepInterval);
      }
    })
      .fail(function(){
        $(messageTarget).html('ERROR: '+data.message);
      });
  };

  /**
   * Funkce pro spuštění načítání...
   */
  this.run = function(){
    sendTaskRequest(url);
  };

};


