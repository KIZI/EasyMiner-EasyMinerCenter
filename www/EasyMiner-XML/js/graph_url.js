function GraphUrlOther(cb,hist,other) {
  var img = document.getElementById('hist'+hist).firstChild;
  var other_url = 'other=' + (cb.checked ? other : '');
  img.src = img.src.replace(/other=[^&]*/,other_url);  
}

function GraphUrlFrom(hist,add,max) {
  var tb = document.getElementById('from'+hist);
  var n = document.getElementById('num'+hist);
  var img = document.getElementById('hist'+hist).firstChild;
  var o = document.getElementById('cbo'+hist).checked ? 1 : 0;
  if(add!=0) {
    tb.value = Number(tb.value)+Number(add);
  }
  if(Number(tb.value) + Number(n.value) - o >= max) {
    tb.value = Number(max) - Number(n.value) + o;
  }
  if(Number(tb.value) < 0) {
    tb.value = 0;
  }
  var from_url = 'from=' + tb.value;
  img.src = img.src.replace(/from=[^&]*/,from_url);  
}

function GraphUrlNum(hist) {
  var tb = document.getElementById('num'+hist);
  var img = document.getElementById('hist'+hist).firstChild;
  var num_url = 'num=' + tb.value;
  img.src = img.src.replace(/num=[^&]*/,num_url);  
}