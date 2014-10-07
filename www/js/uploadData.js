function renderPreview(){
  console.log('sem...');
  var datasetPreview=$('#datasetPreview');
  datasetPreview.hide();
  $('#datasetPreviewLoading').show();
  datasetPreview.load(previewPath,{
    'file':$('#frm-importCsvForm-file').val(),
    'separator':$('#frm-importCsvForm-separator').val(),
    'encoding':$('#frm-importCsvForm-encoding').val(),
    'enclosure':$('#frm-importCsvForm-enclosure').val(),
    'escape':$('#frm-importCsvForm-escape').val()
  },function(){
    $('#datasetPreviewLoading').hide();
    $(this).show();
  });
}


$(document).ready(function(){
  $('#frm-importCsvForm-separator').change(renderPreview);
  $('#frm-importCsvForm-encoding').change(renderPreview);
  $('#frm-importCsvForm-enclosure').change(renderPreview);
  $('#frm-importCsvForm-escape').change(renderPreview);
  $('#frm-importCsvForm-separator').change();
});
