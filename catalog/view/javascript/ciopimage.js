var ciactive_theme = '';
var ci_activeImg = '';
var ci_activeImgPopup = '';
var ci_activeImgindex = '';
function ciopImagej3(ciopimage, ciopimagepopup, el) {

  var $swiper_active = $('.main-image .swiper-slide-active');
  var $img = $('.main-image .swiper-slide-active img').first();
  var $html = $('html');
  var $ImageZoom = $img.data('imagezoom');


  $('.additional-images').undelegate('.additional-image', 'click').delegate('.additional-image', 'click', function () {

    var ciopsrc = $img.attr('data-ciopsrc');
    var ciophref = $img.attr('data-ciophref');

    $img.attr('src',ciopsrc)
    .attr('data-largeimg', ciophref)
    .data('largeimg', ciophref)
    .attr('srcset', ciopsrc+' 1x, '+ciophref + ' 2x');

    var gallery_images = $($swiper_active.attr('data-gallery')).attr('data-oimages');
    if(gallery_images) {
      gallery_images = JSON.parse( gallery_images );
      $($swiper_active.attr('data-gallery')).attr('data-images', JSON.stringify(gallery_images)).data('images', (gallery_images))
    }

    if ($($swiper_active.attr('data-gallery')).data('lightGallery')) {
      $($swiper_active.attr('data-gallery')).data('lightGallery').destroy(true);
    }

    // image zoom
    if (!('ontouchstart' in document)) {
      if ($html.hasClass('route-product-product')) {
        // console.log(ciopsrc);
        // console.log(ciophref);
        $ImageZoom.changeImage(ciopsrc,ciophref);
      }
    }

    if ($.fn.elevateZoom) {
      $img.data('zoom-image',ciopimagepopup);
      $img.attr('data-zoom-image',ciopimagepopup);
      $img.elevateZoom('swaptheimage');
    }

    // reinitialize magnificpop so popup pic new image
    $('.thumbnails').magnificPopup({
      type:'image',
      delegate: 'a',
      gallery: {
      enabled:true
      }
    });
  });

  var ciopimagegalleryThumb = '';
  if(el) {
    ciopimagegalleryThumb = el.attr('data-ciopimagegalleryThumb');
  }
  
  if(!ciopimage ||  !ciopimagepopup) {
    ciopimage = $img.attr('data-ciopsrc');
    ciopimagepopup = $img.attr('data-ciophref');
    ciopimagegalleryThumb = $img.attr('data-ciopgalleryThumb');
  }

  $img.attr('src',ciopimage)
  .attr('data-largeimg', ciopimagepopup)
  .data('largeimg', ciopimagepopup)
  .attr('srcset', ciopimage+' 1x, '+ciopimagepopup + ' 2x');

  var gallery_images = $($swiper_active.attr('data-gallery')).attr('data-oimages');
  if(gallery_images) {
    gallery_images = JSON.parse( gallery_images );
    // console.log(gallery_images);
    var index = parseInt($swiper_active.attr('data-index'), 10) || 0;
    // console.log("index : " + index)
    var gallery_image = {'src' : ciopimagepopup , 'thumb' : ciopimagegalleryThumb , 'subHtml' : gallery_images[index]['subHtml'] };    
    gallery_images[index] = gallery_image;

    $($swiper_active.attr('data-gallery')).attr('data-images', JSON.stringify(gallery_images)).data('images', (gallery_images))
  }

  if ($($swiper_active.attr('data-gallery')).data('lightGallery')) {
    $($swiper_active.attr('data-gallery')).data('lightGallery').destroy(true);
  }

  // image zoom
  if (!('ontouchstart' in document)) {
    if ($html.hasClass('route-product-product')) {
      $ImageZoom.changeImage(ciopimage,ciopimagepopup);
    }
  }

  if ($.fn.elevateZoom) {
    $img.data('zoom-image',ciopimagepopup);
    $img.attr('data-zoom-image',ciopimagepopup);
    $img.elevateZoom('swaptheimage');
  }

  // reinitialize magnificpop so popup pic new image
  $('.thumbnails').magnificPopup({
    type:'image',
    delegate: 'a',
    gallery: {
    enabled:true
    }
  });
}

function ciopImage(ciopimage, ciopimagepopup, el) {
  // console.log(ciactive_theme);
  if(ciactive_theme=='journal3') {
    ciopImagej3(ciopimage, ciopimagepopup, el);
    return;
  }

  var index = 0;
  if(!ciopimage ||  !ciopimagepopup) {
    // ciopimage = $('.ciopimage-thumb').find('img').attr('data-ciopsrc');
    // ciopimagepopup = $('.ciopimage-thumb').attr('data-ciophref');

    ciopimage = $('a[data-ciophref]').first().find('img').attr('data-ciopsrc');
    ciopimagepopup = $('a[data-ciophref]').attr('data-ciophref');
  }

  $('a[data-ciophref]').attr('href',ciopimagepopup);
  $('a[data-ciophref]').find('img').attr('src',ciopimage);

  if ($.fn.elevateZoom) {
    $('a[data-ciophref]').find('img').data('zoom-image',ciopimagepopup);
    $('a[data-ciophref]').find('img').attr('data-zoom-image',ciopimagepopup);
    $('a[data-ciophref]').find('img').elevateZoom('swaptheimage');
  }

  if(typeof Journal != 'undefined' && typeof Journal.changeProductImage != 'undefined') {
    Journal.changeProductImage(ciopimage, ciopimagepopup, index);
  }

  // reinitialize magnificpop so popup pic new image
  $('.thumbnails').magnificPopup({
    type:'image',
    delegate: 'a',
    gallery: {
    enabled:true
    }
  });
}

$(document).ready(function() {

  if ($.fn.elevateZoom) {
    $('#image-additional-carousel a').on('click', function() {
      var zoom_image = $(this).attr('data-zoom-image');
      $('#image').data('zoom-image',zoom_image);
      $('#image').attr('data-zoom-image',zoom_image);
      $('#image').elevateZoom('swaptheimage');
    });
  }

  $('#product').delegate('select, input[type=\'radio\'], input[type=\'checkbox\']', 'change', function() 
  {

    var $this = $(this);
    var el = '';
    var eltype = '';
    var action = '';

    if(($this.attr("type")=="radio" || $this.attr("type")=="checkbox") && $this.is("input") && $this.prop("checked")) {
      
       el = $this;
       eltype = $this.attr("type");
       action = 'click';
    } else if($this.is("select") && $this.val()) {
      el = $this.find("option:selected");
      eltype = 'select';
      action = 'click';
    } else {
      // check if there is any other radio/checkbox or select is selected
      
      $.each($('#product').find('select, input[type=\'radio\'], input[type=\'checkbox\']'), function() {

        var $thiss = $(this);
        if(($thiss.attr("type")=="radio" || $thiss.attr("type")=="checkbox")  && $thiss.is("input") && $thiss.prop("checked") ) {
          var ciopimage = $thiss.attr('data-ciopimage');
          if(ciopimage) {
            el = $thiss;
            eltype = $thiss.attr("type");
            action = 'refresh';
          }
        }

        if($thiss.is("select") && $thiss.val()) {
            var el1 = $thiss.find("option:selected");
            var ciopimage = el1.attr('data-ciopimage');
            if(ciopimage) {
              el = el1;
              eltype = 'select';
              action = 'refresh';
          }
        }

      });

    }

    var ciopimage = '', ciopimagepopup = '';
    if(el) {
      ciopimage = el.attr('data-ciopimage');
      ciopimagepopup = el.attr('data-ciopimagepopup');

      if(ciopimage) {
        ciopImage(ciopimage, ciopimagepopup, el);
      } else if(eltype != 'checkbox' && action == 'click') {
        if(ciopimage) {
          ciopImage(ciopimage, ciopimagepopup, el);
        }
      }
    } else {
      ciopImage(ciopimage, ciopimagepopup, el);
    }
  });
});