(function(window, $) {
  function ensureAlertRoot() {
    if ($('#app-alert-root').length) {
      return;
    }

    $('body').append(
      '<div id="app-alert-root" class="app-alert-root" hidden>' +
        '<div class="app-alert-backdrop"></div>' +
        '<section class="app-alert-card" role="dialog" aria-modal="true" aria-labelledby="app-alert-title">' +
          '<div class="app-alert-icon" aria-hidden="true"></div>' +
          '<div class="app-alert-content">' +
            '<h2 id="app-alert-title">Notice</h2>' +
            '<p id="app-alert-message"></p>' +
            '<div class="app-alert-actions"></div>' +
          '</div>' +
        '</section>' +
      '</div>'
    );
  }

  function setAlertState(type) {
    $('#app-alert-root')
      .removeClass('alert-success alert-error alert-warning alert-info')
      .addClass('alert-' + (type || 'info'));
  }

  function showAlert(options) {
    ensureAlertRoot();

    var settings = $.extend({
      title: 'Notice',
      message: '',
      type: 'info',
      confirmText: 'OK',
      cancelText: '',
      showCancel: false
    }, options || {});

    return new Promise(function(resolve) {
      var $root = $('#app-alert-root');
      var $actions = $root.find('.app-alert-actions');

      setAlertState(settings.type);
      $('#app-alert-title').text(settings.title);
      $('#app-alert-message').text(settings.message);

      $actions.empty();
      if (settings.showCancel) {
        $actions.append('<button type="button" class="btn btn-secondary app-alert-cancel">' + settings.cancelText + '</button>');
      }
      $actions.append('<button type="button" class="btn btn-primary app-alert-confirm">' + settings.confirmText + '</button>');

      $root.prop('hidden', false);
      setTimeout(function() {
        $root.addClass('is-open');
        $root.find('.app-alert-confirm').trigger('focus');
      }, 10);

      function close(result) {
        $root.removeClass('is-open');
        setTimeout(function() {
          $root.prop('hidden', true);
          $actions.off('click', 'button');
          resolve(result);
        }, 180);
      }

      $actions.on('click', '.app-alert-confirm', function() {
        close(true);
      });

      $actions.on('click', '.app-alert-cancel', function() {
        close(false);
      });
    });
  }

  window.appAlert = function(message, type, title) {
    return showAlert({
      title: title || (type === 'success' ? 'Success' : type === 'error' ? 'Something went wrong' : 'Notice'),
      message: message,
      type: type || 'info'
    });
  };

  window.appConfirm = function(message, options) {
    options = options || {};

    return showAlert({
      title: options.title || 'Please confirm',
      message: message,
      type: options.type || 'warning',
      confirmText: options.confirmText || 'Confirm',
      cancelText: options.cancelText || 'Cancel',
      showCancel: true
    });
  };
})(window, jQuery);
