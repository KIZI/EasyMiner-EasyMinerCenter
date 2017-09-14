/**
 * Scripts for drawing of a histogram
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

'use strict';

/**
 * Method for drawing of a vertical histogram
 * @param histogramBlock
 * @param labels : array
 * @param frequencies : array
 * @param height : int
 */
var drawVerticalHistogram=function(histogramBlock, labels, frequencies, height){
  histogramBlock.html('<canvas id="valuesHistogram" width="'+(histogramBlock.innerWidth())+'" height="'+height+'"></canvas>');
  var canvasContext = document.getElementById("valuesHistogram").getContext("2d");

  new Chart(canvasContext, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Frequency',
        backgroundColor: '#2BB0D7',
        borderWidth: 0,
        data: frequencies
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      legend: {
        display: false
      },
      scales: {
        yAxes: [
          {
            ticks: {
              beginAtZero: true
            }
          }
        ],
        xAxes: [
          {
            type: 'category',
            labels: labels
          }
        ]
      }
    }
  });
};

/**
 * Method for drawing of a vertical histogram
 * @param histogramBlock
 * @param labels : array
 * @param frequencies : array
 */
var drawHorizontalHistogram=function(histogramBlock, labels, frequencies){
  var height = 50 + labels.length*20;
  histogramBlock.html('<canvas id="valuesHistogram" width="'+(histogramBlock.innerWidth())+'" height="'+height+'"></canvas>');
  var canvasContext = document.getElementById("valuesHistogram").getContext("2d");

  new Chart(canvasContext, {
    type: 'horizontalBar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Frequency',
        backgroundColor: '#2BB0D7',
        borderWidth: 0,
        data: frequencies
      }]
    },
    options: {
      responsive: false,
      legend: {
        display: false
      },
      scales: {
        xAxes: [
          {
            ticks: {
              beginAtZero: true
            }
          }
        ],
        yAxes: [
          {
            type: 'category',
            labels: labels
          }
        ]
      }
    }
  });
};
