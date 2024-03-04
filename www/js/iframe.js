(function ($, root) {
  $(document).ready(function(){
    window.parent.postMessage({
      type: 'iframe-ready',
    });
    console.log('iframe-ready');
  });
})(jQuery, this);