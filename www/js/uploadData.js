function renderPreview(){
  var datasetPreview=$('#datasetPreview');
  datasetPreview.hide();
  $('#datasetPreviewLoading').show();
  datasetPreview.load(previewPath,{
    'file':$('#frm-file').val(),
    'separator':$('#frm-separator').val(),
    'encoding':$('#frm-encoding').val(),
    'enclosure':$('#frm-enclosure').val(),
    'escape':$('#frm-escape').val()
  },function(){
    $('#datasetPreviewLoading').hide();
    $(this).show();
  });
}


$(document).ready(function(){
  $('#frm-separator').change(renderPreview);
  $('#frm-encoding').change(renderPreview);
  $('#frm-enclosure').change(renderPreview);
  $('#frm-escape').change(renderPreview);
  $('#frm-separator').change();
});
