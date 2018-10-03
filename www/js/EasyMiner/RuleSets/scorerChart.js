/**
 * Scripts for drawing of a chart of scoring results
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

'use strict';

/**
 * Method for drawing of a pie chart
 * @param chartBlock
 * @param labels : array
 * @param data : array
 * @param dataColors : array
 */
var drawPieChart=function(chartBlock, labels, data, dataColors){
  chartBlock.html('<canvas id="pieChart" width="'+(chartBlock.innerWidth())+'" height="'+(chartBlock.innerHeight())+'"></canvas>');
  var canvasContext = document.getElementById("pieChart").getContext("2d");

  new Chart(canvasContext, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        backgroundColor: dataColors,
        data: data
      }]
    },
    options: {
      responsive: true
    }
  });
};