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

  /**
   * Funkce pro spuštění načítání...
   */
  this.run = function(){
    sendTaskRequest(url);
  };

};


