
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

    $('.tk-skillSlider').each(function () {
      var labels = $(this).data('slider-labels').split(',');
      var ticks = Array.apply(null, {length: labels.length}).map(Number.call, Number);

      $(this).on('change', function (e) {
        //console.log('Current value: ' + $(this).val());
      }).attr('data-slider-value', function (e) {
        return $(this).val();
      }).wrap('<div class="slide-wrap"></div>').bootstrapSlider({
        ticks: ticks,
        ticks_labels: labels,
        tooltip: 'hide',
        formatter: function (value) {
          return labels[value];
        }
      });
      if ($(this).prop('disabled') || $(this).prop('readonly')) {
        $(this).bootstrapSlider('disable');
      }
    });
  }

});
