'use strict';

var editor = (function() {
  var active = {
    ev: {},
    id: 0
  };
  var $cal = $('#calendar');
  var saving = false;
  var success = false;
  var popoverOptions = {
    container: 'body',
    html: true,
    placement: 'auto right',
    content: $('.new-event').html()
  };

  // Hide currently open popover
  var close = function() {
    $('.input-date.d2').datetimepicker('destroy');
    $('.input-date.d1').datetimepicker('destroy');
    $('.input-time.t2').datetimepicker('destroy');
    $('.input-time.t1').datetimepicker('destroy');
    $('.popover').remove();
    active.id = 0;
  };

  var calById = function(id) {
    for (var i = 0, len = calendars.length; i < len; i++) {
      if (calendars[i].id === id)
        return calendars[i];
    }
    return null;
  };

  // Fixes impossible date ranges
  var logic = function() {
    var x = $('.popover');
    if (!x) return;
    var start = moment(x.find('.d1').val() + 'T' + x.find('.t1').val());
    var end = moment(x.find('.d2').val() + 'T' + x.find('.t2').val());
    if (start.isAfter(end)) {
      start = start.add(1, 'hour');
      $('.input-date.d2').val(start.format('YYYY-MM-DD'));
      $('.input-time.t2').val(start.format('HH:mm'));
    }
  };

  // Initialize popover
  var init = function() {

  };

  // Delete event by id
  var remove = function() {
    if (!active.id) return;
    api.deleteEvent(active.id).success(function(data) {
      close();
      console.log()
      $cal.fullCalendar('removeEvents', active.ev.id);
      active.ev.id = 0;
    }).error(function(data) {
      close();
    });
  };

  // Sync with backend
  var sync = function(data, revert) {
    data.id = data.id || active.ev.id;
    data.title = data.title || active.ev.title;
    data.calendar = data.calendar_id || active.ev.calendar_id; // TODO: fix API and remove this line
    data.calendar_id = data.calendar_id || active.ev.calendar_id;

    api.postEvent(data).success(function(data) {
      $cal.fullCalendar('refetchEvents');
    }).error(function(error) {
      console.log(error)
    });
  };

  // Move or resize event
  var drop = function(ev, delta, revert) {
    console.log(ev)
    active.ev = ev;
    var data = {
      start: ev.start.format('YYYY-MM-DD HH:mm'),
      allDay: !ev.start.hasTime()
    };
    if (ev.end) {
      data.end = ev.end.format('YYYY-MM-DD HH:mm');
    } else {
      data.end = ev.start.add(1, 'day').format('YYYY-MM-DD HH:mm');
    }

    sync(data, revert);
  };

  // Update existing event
  var update = function(data) {
    sync(data);

    // Update existing event
    active.ev.title = data.title;
    active.ev.description = data.description;
    active.ev.location = data.location;
    active.ev.start = Date.parse(data.start);
    active.ev.end = Date.parse(data.end);
    active.ev.calendar_id = data.calendar_id;
    active.ev.allDay = data.allDay;

    // Render
    $cal.fullCalendar('updateEvent', active.ev);
  };

  // Create new event
  var create = function(e) {
    e.preventDefault();
    var x = $('.popover');

    // Retrieve form values
    var formdata = {
      title: x.find('.input-title').val(),
      description: x.find('.input-descr').val(),
      location: x.find('.input-location').val(),
      start: x.find('.d1').val() + ' ' + x.find('.t1').val() || '00:00',
      end: x.find('.d2').val() + ' ' + x.find('.t2').val() || '00:00',
      allDay: x.find('.input-allday').prop('checked') ? '1' : '0',
      calendar_id: x.find('.input-cals').val(),
    };

    // Validate input?
    if (!formdata.title.length) {
      $('.popover .input-title').on('keyup', function() {
          $(this).parent().removeClass('label--error')
        })
        .parent().addClass('label--error').find('span').text('Titel vereist!');
      return false;
    }

    // Update
    if (active.ev._id) {
      close();
      update(formdata);
      return;
    }

    // Apply to backend
    api.postEvent(formdata).success(function(data) {
      close();
      $cal.fullCalendar('refetchEvents');
    }).error(function(error) {
      console.log(error)
    });

    // Root calendar is always opened
    if (!calendars[formdata.calendar_id].parent_id) return;

    // Open calendar that event was added to
    var label = $('[data-cal="' + formdata.calendar_id + '"]');
    if (label.hasClass('level--0') && !label.find('input').prop('checked')) {
      label.click();
      label.toggleClass('active', true);
    } else if (label.hasClass('level--1')) {
      label.click();
      // Get child labels
      label = $('label[data-parent="' + formdata.calendar_id + '"]');
      label.click();
      label.toggleClass('active', true);
    }
  };

  // Default options
  var d1Options = {
    format: 'Y-m-d',
    timepicker: false,
    onChangeDateTime: logic,
    onShow: logic
  };
  var d2Options = $.extend({}, d1Options);
  var t1Options = {
    format: 'H:i',
    datepicker: false,
    onChangeDateTime: logic,
    onShow: logic,
    step: 30
  };
  var t2Options = $.extend({}, t1Options);

  var fixPopup = function(editable) {
    var $popover = $('.popover');
    $popover.on('click', function(e) {
      e.stopPropagation();
    });
    if ($popover.css('left').replace('px', '') < 0) {
      $popover.css('left', '30px');
    }

    if (editable) {

      // Launch datetimepicker
      $('.input-date.d2').datetimepicker(d2Options);
      $('.input-date.d1').datetimepicker(d1Options);
      $('.input-time.t2').datetimepicker(t2Options);
      $('.input-time.t1').datetimepicker(t1Options);

      // Listen to allDay toggle
      $('.input-allday').on('change', toggleAllDay);
    }
  };

  // Toggle allDay to reflect parameter active
  var toggleAllDay = function(e, allDay) {
    if (e) {
      allDay = $(this).prop('checked');
    } else {
      $('.input-allday').prop('checked', allDay);
    }
    $('.input-time').prop('disabled', allDay);
  };

  // Add event from scratch
  var add = function(jsEvent) {

    jsEvent.stopPropagation();

    // Close current popover
    close();

    if (!user.logged_in) return;

    d1Options.value = moment().format('YYYY-MM-DD');
    t1Options.value = '08:00';
    d2Options.value = moment().format('YYYY-MM-DD');
    t2Options.value = '09:00';

    // Launch popover
    var $target = $(jsEvent.target);
    $target.popover(popoverOptions);
    $target.popover('show');

    fixPopup(true);
    toggleAllDay(null, true);

    $('.popover .btn-danger').addClass('invisible');
  };

  // Show popover to create event
  var select = function(start, end, jsEvent, view) {

    // Close current popover
    close();

    if (!user.logged_in) return;

    // Create new event or edit existing event
    d1Options.value = start.format('YYYY-MM-DD');
    t1Options.value = '08:00';
    t2Options.value = '09:00';

    // If time is set, use it
    var allDay = true;
    if (start.hasTime()) {
      t1Options.value = start.format('HH:mm');
      d2Options.value = end.format('YYYY-MM-DD');
      t2Options.value = end.format('HH:mm');
      allDay = false;
    }

    // If one day selected
    else if (end.diff(start, 'days') === 1) {
      d2Options.value = start.format('YYYY-MM-DD');
    }

    // If multiple days
    else {
      d2Options.value = end.subtract(1, 'day').format('YYYY-MM-DD');
    }

    // Launch popover
    var $target = $(jsEvent.target);
    $target.popover(popoverOptions);
    $target.popover('show');

    fixPopup(true);
    toggleAllDay(null, allDay);

    $('.popover .btn-danger').addClass('invisible');
  };

  var open = function(ev, jsEvent, view) {

    // Close current popover
    close();

    var $target = $(jsEvent.target);

    if (ev.editable) {

      d1Options.value = ev.start.format('YYYY-MM-DD');
      t1Options.value = ev.start.format('HH:mm');
      if (ev.end) {
        d2Options.value = ev.end.format('YYYY-MM-DD');
        t2Options.value = ev.end.format('HH:mm');
      } else {
        d2Options.value = ev.start.format('YYYY-MM-DD');
        t1Options.value = ev.start.format('08:00');
        t2Options.value = ev.start.format('09:00');
      }
      active.ev = ev;
      active.id = ev.id || 0;

      // Launch popover
      $target.popover(popoverOptions);
      $target.popover('show');
      $('.popover .btn-success').text('Opslaan');
      toggleAllDay(null, active.ev.allDay);
      $('.input-allday').on('change', toggleAllDay);

      // Launch datetimepicker
      $('.input-date.d2').datetimepicker(d2Options);
      $('.input-date.d1').datetimepicker(d1Options);
      $('.input-time.t2').datetimepicker(t2Options);
      $('.input-time.t1').datetimepicker(t1Options);

      // Set input fields
      $('.input-title').val(ev.title);
      $('.input-descr').val(ev.description);
      $('.input-location').val(ev.location);
      $('.input-cals option[value="' + ev.calendar_id + '"]').prop('selected', true);
      fixPopup(true);
    } else {

      // Set input fields
      $('.read-title').text(ev.title);
      $('.read-descr').text(ev.description);
      $('.read-location').text(ev.location);
      $('.read-cal').text('Kalender ' + calendars[ev.calendar_id].name);

      // Remove empty fields
      if (!ev.description) $('.read-descr').remove();
      if (!ev.location) $('.read-location').remove();

      var a = ev.start.format('MMM');
      var b = ev.start.format('DD');
      var c = ev.end ? ev.end.format('MMM') : ev.start.format('MMM');
      var d = ev.end ? ev.end.format('DD') : ev.start.format('DD');

      // Create readable date
      var readableDate = b;
      if (ev.allDay || ev.start.format('HHmm') === '0000') {
        if (a === c && b == d) {
          readableDate += ev.start.format(' MMMM');
        } else if (a === c) {
          readableDate += ev.end.format(' - DD MMMM');
        } else {
          readableDate += ev.start.format(' MMMM') + ev.end.format(' - DD MMMM');
        }
      } else {
        readableDate += ev.start.format(' MMMM HH:mm');
        if (a === c && b === d) {
          readableDate += ev.end.format(' - HH:mm');
        } else {
          readableDate += ev.end.format(' - DD MMMM HH:mm');
        }
      }
      $('.read-dt').text(readableDate);
      $target.popover({
        container: 'body',
        html: true,
        placement: 'auto right',
        content: $('.read-event-template').html()
      });
      $target.popover('show');
      fixPopup();
    }
  };

  window.addEventListener('submit', create);

  return {
    active: active,
    add: add,
    close: close,
    drop: drop,
    init: init,
    logic: logic,
    open: open,
    remove: remove,
    select: select
  };
})();
