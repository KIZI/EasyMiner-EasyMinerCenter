function ShowHide(id) {
  displayed = document.getElementById(id).nodeName == 'TBODY' ? 'table-row-group' : 'block';
  folding = document.getElementById(id).style;
  folding.display = (folding.display == displayed) ? 'none': displayed;
}

function ShowChecked(cb,id) {
  displayed = document.getElementById(id).nodeName == 'TBODY' ? 'table-row-group' : 'block';
  folding = document.getElementById(id).style;
  folding.display = (cb.checked)? displayed : 'none';
}

function Show(a,id) {
  var el = document.getElementById(id);
  var prev = a.previousElementSibling;

  el.style.display = 'block';
  if(prev && prev.type == 'checkbox') {
    prev.checked = true;
  }
}
