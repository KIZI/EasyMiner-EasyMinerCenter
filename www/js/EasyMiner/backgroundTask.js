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
  var returnPostMessage= params.returnPostMessage ? params.returnPostMessage : false;
  var type=params.type ? params.type : false;
  var attributes= params.attributes ? params.attributes : null;

  var sendTaskRequest = function(){
    jQuery.getJSON(
      url,
      function(data){
        if (data!=undefined){
          $(messageTarget).html(data.message);
          if (returnPostMessage){
            window.parent.postMessage({
              type: type,
              message: data.message,
              state: data.state,
              attributes: attributes
            })
          }

          if (data.redirect!=undefined && data.redirect!=''){
            if (!returnPostMessage){
              location.href=data.redirect;
            }
            return;
          }
        }
        setTimeout(function(){
          sendTaskRequest();
        }, sleepInterval);
      }
    )
      .fail(function(data){
        $(messageTarget).html('<div class="error">ERROR occured during preprocessing task.</div>');
        if (returnPostMessage){
          window.parent.postMessage({
            type: type,
            message: data.message,
            state: data.state,
            attributes: attributes
          })
        }else{
          $(messageTarget).append('<div style="text-align:center;"><a href="#" onclick="parent.reload();" class="button" >OK</a></div>');
        }
      });
  };

  this.run = function(){
    sendTaskRequest(url);
  };

};


