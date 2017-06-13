/**
 * @class BackgroundTask - javascript component for running of background requests for long running tasks
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @param {Object} [params={}]
 * @constructor
 */
var BackgroundTask = function(params){
  var url=params.url;
  var sleepInterval = params.sleep ? params.sleep : 500;
  var messageTarget = params.messageTarget;

  var sendTaskRequest = function(){
    jQuery.getJSON(
      url,
      function(data){
        if (data!=undefined){
          $(messageTarget).html(data.message);
          if (data.redirect!=undefined && data.redirect!=''){
            location.href=data.redirect;
            return;
          }
        }
        setTimeout(function(){
          sendTaskRequest();
        }, sleepInterval);
      }
    )
      .fail(function(data){
        $(messageTarget).html('<div class="error">ERROR occured during preprocessing task.</div><div style="text-align:center;"><a href="#" onclick="parent.reload();" class="button" >OK</a></div>');
      });
  };

  this.run = function(){
    sendTaskRequest(url);
  };

};


