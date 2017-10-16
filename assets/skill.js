
jQuery(function($) {

  // TODO: change the color of the slider as it slides....
  // TODO: move this to the plugin folder
  function getGreenToRed(percent) {
    function decToHex(c) {
      var hex = c.toString(16);
      return hex.length === 1 ? "0" + hex : hex;
    }
    r = percent < 50 ? 255 : Math.floor(255 - (percent * 2 - 100) * 255 / 100);
    g = percent > 50 ? 255 : Math.floor((percent * 2) * 255 / 100);
    var hex = '#' + decToHex(r) + decToHex(g) + '00';
    //var rgb = 'rgb('+r+','+g+',0)';
    return hex.toUpperCase();
  }

  // Bootstrap Slider
  if ($.fn.bootstrapSlider !== undefined) {
    var labels = ['Not Assessed', 'Unable', 'Developing', 'Acceptable', 'Good', 'Exceptional'];
    var ticks = [0, 1, 2, 3, 4, 5];

    $('.tk-skillSlider').on('change', function (e) {
      //console.log('Current value: ' + $(this).val());
      //console.log(arguments);
      // var value = $(this).val();
      // var color = getGreenToRed((value / (labels.length-1)) * 100);
      // $(this).closest('.slider').find('.slider-track .tick-slider-selection').css('background', color);
      // console.log($(this).closest('.slider'));

    }).attr('data-slider-value', function (e) {
      return $(this).val();
    }).wrap('<div class="slide-wrap"></div>').bootstrapSlider({
      ticks: ticks,
      //ticks_labels: ['Not Assessed', 'Unable', 'Developing', 'Acceptable', 'Good', 'Exceptional'],
      //tooltip: 'hide'
      formatter: function (value) {
        return labels[value];
      }
    });

  }

});
