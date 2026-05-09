<?php
// $this->session->userdata('user_name')
// $this->session->userdata('user_email')
// $this->session->userdata('user_type')
$user_type = $this->session->userdata('user_type');
?>

  <style>

    .error-row {
        background-color: #f8d7da !important;
    }

    .autocomplete-wrapper {
      position: relative;
      margin-bottom: 20px;
    }

    .result-item {
      padding: 4px;
      cursor: pointer;
    }

    .result-item.selected {
      background-color: #007bff;
      color: white;
    }

    .result-item:hover {
      background-color: #f0f0f0;
    }

    .results {
      max-height: 150px;
      overflow-y: auto;
    }


/* button */
.btn-ins {
    background-color: #a0c050;
    color: white;
    padding: 5px 10px; 
    border: none;
    border-radius: 4px; 
    margin-left: 0;
}
.btn-del {
    background-color: #ff6060;
    color: white;
    padding: 2px;
    margin: 0 15px;
    border: none;
    border-radius: 4px;
    margin-left: 0;
}

td.button-cell {
  display: flex;
  gap: 0.5rem; /* Abstand zwischen Buttons */
  align-items: center;
}

/* Time Picker Stuff */
td {
  position: relative; /* Eltern für absolute Positionierung */
}
.timePicker {
  position: absolute;
  top: 100%; /* direkt unterhalb der Zelle */
  left: 0;
  background: white;
  border: 1px solid #ccc;
  padding: 4px;
  display: none;
  z-index: 10;
  width: auto;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15); /* optional für besseren Look */
}
.picker-grid {
  display: flex;
  gap: 10px;
}
.picker-grid .col {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.picker-arrow {
  cursor: pointer;
  user-select: none;
  padding: 2px;
  font-size: 16px;
}
.picker-value {
  font-weight: bold;
  margin: 2px 0;
}

</style>

<div id="headerbar">

<?php _trans('enter_worktime'); 
 $this->load->helper('user_helper');
?>

<div id="content" class="table-content">
<div>

<!-- -->
<?php if ($user_type == 1): ?>

<a href="<?php echo site_url('timesheets/index/'. $user->user_id.'/'.$month.'/'.$year); ?>" 
    class="btn btn-sm btn-primary"> 
<i class="fa fa-binoculars"></i><?= trans('overview') ?></a>

<a href="<?php echo site_url('timesheets/form/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheet_input') ?></a>

<a href="<?php echo site_url('timesheets/view/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheets_view') ?></a>

<?php else: ?>

<a href="<?php echo site_url('employee/timesheets/index/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('overview') ?></a>

<a href="<?php echo site_url('employee/timesheets/form/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheet_input') ?></a>

<a href="<?php echo site_url('employee/timesheets/view/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheets_view') ?></a>

<?php endif; ?>
<!-- -->

<br> <br>
<?php $this->layout->load_view('layout/alerts'); ?>
<!-- -->

<!-- date, userinfo with change -->
<table ><tr><td style="padding: 10px 20px 0 0 ">
    <i class="fa fa-calendar" title=""></i>
    <span > <?= $month ?>.<?= $year ?></span>
<br>
<form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>" >
        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
        value="<?php echo $this->security->get_csrf_hash() ?>">

<!-- change date -->
        <select id="my_month" name="my_month">
        <?php  for($i=1; $i<=12; $i++) {
                echo '<option value="'.$i.'"';
                if ($month == $i) echo ' selected="selected" ';
                echo ' >'.$i.'</option>';
                echo "\n";
        } ?>
        </select>

        <select id="my_year" name="my_year">
        <?php  for ($y = date("Y"); $y >= date("Y")-5; $y--) {
                echo '<option value="'.$y.'"';
                if ($year == $y) echo ' selected="selected" ';
                echo ' >'.$y.'</option>';
                echo "\n";
        } ?>
        </select>

        <input type="submit" class="btn"  name="btn_submit_user" value="<?php _trans('change'); ?>">
</td><td>
   <!-- show user -->
<?php show_user($user->user_name, $user->user_email); ?>
<br>

<!-- change user -->
<?php if ($user_type == 1): ?>
    <select id="my_userid" name="my_userid">
    <?php
        foreach ($users as $u) {
                echo '<option value="'.$u->user_id.'"';
                if ($user_id == $u->user_id) echo ' selected="selected" ';
                echo ">$u->user_name ($u->user_id)</option>";
                echo "\n";
        } ?>
        </select>
        <input type="submit" class="btn"  name="btn_submit_user" value="<?php _trans('change'); ?>">
<?php endif; ?>

</form>
</td></tr></table>
<!-- END userinfo with change -->

<br>
    <i class="fa fa-calculator" title=""></i>
    <span > <?= _trans('sum_month'); ?>:  <span id="all_day_result" style="color: green; font-weight:bold;">00:00</span>

<br> <br>

    <a href="#" class="btn btn-sm btn-primary btn_calc_worktime"> <i class="fa fa-calendar"></i><?= trans('calculate') ?> </a>
    <a href="#" class="btn btn-sm btn-primary btn_check_worktime"> <i class="fa fa-check"></i><?= trans('check') ?> </a>
    <a href="#" class="btn btn-sm btn-success btn_save_worktime"> <i class="fa fa-check"></i><?= trans('save') ?> </a>

<br><br>

<!-- -->

<script>
/*!
* JavaScript UUID Generator, v0.0.1
*
* Copyright (c) 2009 Massimo Lombardo.
* Dual licensed under the MIT and the GNU GPL licenses.
*/
function UUID() {
    var uuid = (function () {
        var i,
            c = "89ab",
            u = [];
        for (i = 0; i < 36; i += 1) {
            u[i] = (Math.random() * 16 | 0).toString(16);
        }
        u[8] = u[13] = u[18] = u[23] = "-";
        u[14] = "4";
        u[19] = c.charAt(Math.random() * 4 | 0);
        return u.join("");
    })();
    return {
        toString: function () {
            return uuid;
        },
        valueOf: function () {
            return uuid;
        }
    };
}

function timeAdd(s1, s2) 
{
  const [h1, m1] = s1.split(':').map(Number);
  const [h2, m2] = s2.split(':').map(Number);

  let totalMinutes = m1 + m2;
  let totalHours = h1 + h2 + Math.floor(totalMinutes / 60);
  totalMinutes %= 60;

  return (
    String(totalHours).padStart(2, '0') + ':' + String(totalMinutes).padStart(2, '0')
  );
}

// subtracts 01:00 from 02:33
function timeDiff(s1, s2) 
{
  const [h1, m1] = s1.split(':').map(Number);
  const [h2, m2] = s2.split(':').map(Number);

  const total1 = h1 * 60 + m1;
  const total2 = h2 * 60 + m2;
  let diff = total1 - total2;

  /* Diese Zeile behandelt den Sonderfall, wenn die Endzeit vor der Startzeit liegt – 
      also wenn die Zeit über Mitternacht hinausgeht. */
  if (diff < 0) diff += 1440;

  const hours = Math.floor(diff / 60);
  const minutes = diff % 60;

  return (
    String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0')
  );
}


/**
 * Time Picker Stuff
 */
function toMinutes(timeStr) {
  const [h, m] = timeStr.split(':').map(Number);
  return h * 60 + m;
}

function toTimeString(minutes) {
  const h = String(Math.floor(minutes / 60)).padStart(2, '0');
  const m = String(minutes % 60).padStart(2, '0');
  return `${h}:${m}`;
}

function updateDuration($row) {
  const start = toMinutes($row.find('.x_from').val());
  const end = toMinutes($row.find('.x_to').val());
  let diff = end - start;
  if (diff < 0) diff += 1440;
  $row.find('.x_hours').val(toTimeString(diff));
}

function updateEndTime($row) {
  const start = toMinutes($row.find('.x_from').val());
  const duration = toMinutes($row.find('.x_hours').val());
  const end = (start + duration) % 1440;
  $row.find('.x_to').val(toTimeString(end));
}

// Picker erstellen 
function createPicker($input, $inputsInRow) 
{
  // Picker div in gleicher Zelle suchen (wenn nicht vorhanden, anlegen)
  let $picker = $input.siblings('.timePicker');
  if ($picker.length === 0) {
    // Picker div anlegen, falls nicht vorhanden
    $picker = $('<div class="timePicker"></div>').appendTo($input.parent());
  }

  $('.timePicker').hide(); // alle Picker schließen

  const time = $input.val();
  const [h, m] = time.split(':').map(Number);

  $picker.html(`
    <div class="picker-grid">
      <div class="col">
        <div class="picker-arrow hour-up">▲</div>
        <div class="picker-value hour">${String(h).padStart(2, '0')}</div>
        <div class="picker-arrow hour-down">▼</div>
      </div>
      <div class="col">
        <div class="picker-arrow minute-up">▲</div>
        <div class="picker-value minute">${String(m).padStart(2, '0')}</div>
        <div class="picker-arrow minute-down">▼</div>
      </div>
    </div>
  `);

  $picker.show();

  $picker.off('click').on('click', '.picker-arrow', function () {
    let hour = parseInt($picker.find('.hour').text());
    let minute = parseInt($picker.find('.minute').text());

    const isDuration = $input.hasClass('x_hours');
    const $row = $input.closest('tr');

    if ($(this).hasClass('hour-up')) hour = (hour + 1) % 24;
    if ($(this).hasClass('hour-down')) hour = (hour - 1 + 24) % 24;
    if ($(this).hasClass('minute-up')) minute = (minute + 15) % 60;
    if ($(this).hasClass('minute-down')) minute = (minute - 15 + 60) % 60;

    $picker.find('.hour').text(String(hour).padStart(2, '0'));
    $picker.find('.minute').text(String(minute).padStart(2, '0'));

    const timeStr = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    $input.val(timeStr);

    if ($input.hasClass('x_from') || $input.hasClass('x_to')) {
      updateDuration($row);
    } else {
      updateEndTime($row);
    }
  });
}

/* add pickers */
$(document).on('focus', '.timeInput', function () {
  const $input = $(this);
  const $row = $input.closest('tr');
  const $inputsInRow = $row.find('.timeInput'); // alle Inputs in der Zeile

  // Picker am aktuellen Input erstellen (in dessen TD)
  createPicker($input, $inputsInRow);
});


/**
 *  jeden tag ansich berechnen
 */
function calc_each_day() 
{
    var d = 1;
    var d_old = 1;
    let day_hours = [];
    let day_cnt = 0;
    day_hours[0] = false;
    day_hours[1] = '00:00';
    let all_day_hours = '00:00';
	$('#x-worktime tr').each(function () {
		d_old = d;
		d = $(this).find('.x_day').val();
		t = $(this).find('.x_hours').val();
		if (t) {
	 	    if (d_old != d ) {
                day_cnt++; 
                day_hours[day_cnt] = '00:00';
            };
			day_hours[day_cnt] = timeAdd(day_hours[day_cnt], t);
			all_day_hours = timeAdd(all_day_hours, t);
			//console.log(d,t);
		};
	});

    //console.log(JSON.stringify(day_hours));
    //console.log(JSON.stringify(all_day_hours));

    for(i=1; i<=31; i++) {
            // update sum table
            $('#x-worktime tr td#sum_'+i).each(function() {
                    $(this).html(day_hours[i]);
                    });
    };
}

/****
 * summe aller tage zusammenzaehlen fuer monatssumme
 */
function calc_all_days() 
{
  let totalMinutes = 0;

  $(".sum").each(function () {
    /* const val = $(this).val(); // <-dies geht nur bei input, textarea, select */
    const val = $(this).text().trim();  // dies bei td
    //console.log("aa->"+$(this).attr('id')+" -- " + val);
    if (val && val.includes(":")) {
      const [h, m] = val.split(":").map(Number);
      if (!isNaN(h) && !isNaN(m)) {
        totalMinutes += h * 60 + m;
      }
    }
  });

  const totalHours = Math.floor(totalMinutes / 60);
  const totalMins = totalMinutes % 60;

  const my_result = `${String(totalHours).padStart(2, "0")}:${String(totalMins).padStart(2, "0")}`;
  $('#all_day_result').html(my_result);
}

//
// sammeln fuers speichern
function collect_items() 
{
    var items = [];	// array
    var item_order = 1;

    //console.log("---");
    // items zum saven suchen und ins array items
    $('#x-worktime tr').each(function () {
            var row = {};	// empty object
            var found = 0;
            $(this).find('input,select,textarea').each(function () {
                    found = 1;
                    if ($(this).is(':checkbox')) {
                    row[$(this).attr('name')] = $(this).is(':checked');
                    } else {
                    row[$(this).attr('name')] = $(this).val();
                    }
                    });
            if (found > 0) {
                row['item_order'] = item_order;
                item_order++;
                items.push(row);
                // extreme debugging
                //if(item_order < 10) {
                //    console.log("row:"+JSON.stringify(row));
                //    }
            }
            });
    return items;
}

/**
 *send for check or save 
 */
let isSaving = false; // Schutz-Flag gegen Mehrfachklicks

function sendWorktimeData(url, onComplete) 
{
    if (isSaving) return;       // double clicked? ignore second click
    isSaving = true;

    $('#fullpage-loader').show();       // show turning gearwheel

    // berechnen fuer den user sonst erschrickt er
    calc_each_day();
    calc_all_days();

    var items = collect_items();
    var $buttons = $('.btn_save_worktime, .btn_check_worktime');
    $buttons.prop('disabled', true);

    //console.log("post url");    // DEBUG
    $.post(url, {
        userid: <?= $user->user_id ?>,
        month: <?= $month ?>,
        year: <?= $year ?>,
        items: JSON.stringify(items),
        '<?= $this->security->get_csrf_token_name(); ?>':
            '<?= $this->security->get_csrf_hash(); ?>'
    })
    .done(function(data) {
        var response;
        try {
          response = JSON.parse(data);
        } catch (e) {
          if (onComplete) onComplete(false, "Antwort konnte nicht gelesen werden.");
          return;
        }

    //console.log("post url"+JSON.stringify(response));   // DEBUG

    // Zuerst alle alten Fehler-Markierungen entfernen
    $('tr').removeClass('error-row');
    if(response) {
        $('#fullpage-loader').hide();
        $('.control-group').removeClass('has-error');
        $('div.alert[class*="alert-"]').remove();
            var r_msg =
             ' Elemente: ' + response.successData.counter + '<br>'
            +' Korrekt : ' + response.successData.correct + '<br>'
            + response.successData.message ;
        if (response.success == true) {
            $('#timesheet_form') .prepend('<div class="alert alert-success">'+r_msg+' erfolgreich.</div>');
            setTimeout(function(){
                $('.control-group').removeClass('has-error');
                $('div.alert[class*="alert-"]').remove();
            },15000);
        } else {
            $('#timesheet_form').prepend('<div class="alert alert-danger">' + r_msg + ' FEHLER1!</div>');
    const failedUuids =response.successData.failed_uuids;
    failedUuids.forEach(uuid => {
        // Finde das input-Feld mit der UUID und dann die übergeordnete tr-Zeile
        const row = $(`input[name="x_uuid"][value="${uuid}"]`).closest('tr');
        row.addClass('error-row');
    });

        }
    }

       isSaving = false;
        $buttons.prop('disabled', false).removeClass('disabled');
        $('#fullpage-loader').hide();
  })
  .fail(function(jqXHR, textStatus, errorThrown) {
        $('#fullpage-loader').hide();
        if (onComplete) onComplete(false, "AJAX Post Error: " + textStatus);
  });
}


<?php if ($user_type == 1): ?>

function sendWorktimeDelete(uuid) {
  $('#fullpage-loader').show();
  $.post('<?= site_url('timesheets/ajax/set_delete'); ?>'  , {
    userid: <?= $user->user_id ?>,
    uuid: uuid,
        '<?= $this->security->get_csrf_token_name(); ?>':
            '<?= $this->security->get_csrf_hash(); ?>'
  })
  .done(function(data) {
  $('#fullpage-loader').hide();
    var response;
    try {
      response = JSON.parse(data);
    } catch (e) {
      return;
    }
    console.log("sendWorktimeDelete - post url"+JSON.stringify(response));
  })
  .fail(function(jqXHR, textStatus, errorThrown) {
    console.log("sendWorktimeDelete - Fehler" + textStatus);
  });
}

<?php else: ?>

function sendWorktimeDelete(uuid) {
  $('#fullpage-loader').show();
  $.post('<?= site_url('employee/ajax/set_delete'); ?>'  , {
    userid: <?= $user->user_id ?>,
    uuid: uuid,
        '<?= $this->security->get_csrf_token_name(); ?>':
            '<?= $this->security->get_csrf_hash(); ?>'
  })
  .done(function(data) {
  $('#fullpage-loader').hide();
    var response;
    try {
      response = JSON.parse(data);
    } catch (e) {
      return;
    }
    console.log("sendWorktimeDelete - post url"+JSON.stringify(response));
  })
  .fail(function(jqXHR, textStatus, errorThrown) {
    console.log("sendWorktimeDelete - Fehler" + textStatus);
  });
}
<?php endif; ?>

function calcCompleteTimesheet() 
{
    // pre-calc each row
    $('.x_from').each(function() {
        const $row = $(this).closest('tr');
        updateDuration($row);
    });

    // calc each day
    calc_each_day();

    // calc complete sheet
    calc_all_days();
}

/**
 * START of document ready 
 */
$(document).ready(function() 
{
    // Initial calc each timesheet line
    $('tr.timestuff').each(function () {
        const $row = $(this);
        updateDuration($row);
    });
    // inital complete calc
    calcCompleteTimesheet();
    // ^this two must be done first!

    // allow changing of start, end time
    $(document).on('change', '.x_from, .x_to', function () {
        const $row = $(this).closest('tr.timestuff');
        updateDuration($row);
    });

    // allow change of hours directly, change end time
    $(document).on('change', '.x_hours', function () {
        const $row = $(this).closest('tr.timestuff');
        updateEndTime($row);
    });

    // click outside closes picker
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.timePicker, .timeInput').length) {
          $('.timePicker').hide();
      }
    });

    // tab key closes picker
    $(document).on('keydown', '.timeInput', function (e) {
      if (e.key === 'Tab') {
          $('.timePicker').hide();
      }
    });

    // calc worktime on button
    $('.btn_calc_worktime').click(function () {
        calcCompleteTimesheet();
    });

    // reset button - also clear in database via ajax
    $(document).on("click", ".btn-reset", function () {
        const $tr = $(this).closest("tr");

        let del_uuid = $tr.find(".x_uuid").val();
        //console.log("del_uuid 0: " + del_uuid);
        sendWorktimeDelete(del_uuid);

        // Felder auf Standard zurücksetzen
        $tr.find(".x_from").val("00:00");
        $tr.find(".x_to").val("00:00");
        $tr.find(".x_hours").val("00:00");
        $tr.find(".x_remark").val("");
        $tr.find(".x_km").val("0");
        $tr.find(".x_type").val("");
        $tr.find(".searchInput").val("");
        $tr.find(".x_customer").val("");
        $tr.find(".kundenId").val("0");

        // reset warning
        $tr.find(".x_hours").css({
              "border": "",
              "background-color": ""
        });
    });

    // KeyPress enter saves,too
    $(document).on("keydown", function(e) {
        if (e.keyCode === 13) {
        $(".btn_save_worktime").click(); // simuliert Button-Klick
      }
    });

// send stuff to server, update_by_uuid, respect user type
<?php if ($user_type == 1): ?>

$('.btn_save_worktime').click(function () {
    calcCompleteTimesheet();
        sendWorktimeData("<?= site_url('timesheets/ajax/update_by_uuid'); ?>", function(success, message) {
            // ajax fehler?
            //console.log("message:"+message);
            if(message)
                $('#timesheet_form').prepend('<div class="alert alert-danger">' + message + ' FEHLER2!</div>');
            });
        });

$('.btn_check_worktime').click(function () {
    calcCompleteTimesheet();
    sendWorktimeData("<?= site_url('timesheets/ajax/check'); ?>", function(success, message) {
        // ajax fehler?
        //console.log("message:"+message);
        if(message)
            $('#timesheet_form').prepend('<div class="alert alert-danger">' + message + ' FEHLER3!</div>');
        });
    });

<?php else: ?>

$('.btn_save_worktime').click(function () {
    calcCompleteTimesheet();
    sendWorktimeData("<?= site_url('employee/ajax/update_by_uuid'); ?>", function(success, message) {
        // ajax fehler?
        //console.log("message:"+message);
        if(message)
            $('#timesheet_form').prepend('<div class="alert alert-danger">' + message + ' FEHLER4!</div>');
        });
    }); 

$('.btn_check_worktime').click(function () {
    calcCompleteTimesheet();
    // console.log("btn_check_worktime: calcCompleteTimesheet() done");    // DEBUG
    // console.log("CSRF: <?php echo $this->security->get_csrf_hash() ?>");
    sendWorktimeData("<?= site_url('employee/ajax/check'); ?>", function(success, message) {
        // ajax fehler?
        if(message)
            $('#timesheet_form').prepend('<div class="alert alert-danger">' + message + ' FEHLER5!</div>');
        });
    });

<?php endif; ?>

});     
/* END document ready */


/***
 * Extensions search as you type
 */

// Kundenliste mit ID und Name
  const kunden = [
<?php foreach ($user_clients as $c) { 
  echo "{ id: $c->client_id, name: \"$c->client_name $c->client_surname ($c->customer_no)\" },\n";
}
?>
  ];
  // END Kundenliste mit ID und Name


// Setup-Funktion von search as you type mit jQuery
function setupAutocomplete($container) {
  const $input = $container.find(".searchInput");
  const $idField = $container.find(".kundenId");
  const $results = $container.find(".results");

  let selectedIndex = -1;

  $input.on("input", function () {
    const query = $(this).val().toLowerCase().trim();
    $results.empty();
    $idField.val("");
    selectedIndex = -1;

    if (query.length === 0) return;

    const filtered = kunden.filter(k =>
      k.name.toLowerCase().includes(query)
    );

    filtered.forEach(kunde => {
      const $item = $("<div>")
        .addClass("result-item")
        /*.text(`${kunde.name} (ID: ${kunde.id})`)*/
        .text(`${kunde.name}`)
        .data("kunde", kunde);

      $results.append($item);
    });
  });

  // Klick auf Eintrag
  $results.on("click", ".result-item", function () {
    const kunde = $(this).data("kunde");
    $input.val(kunde.name);
    $idField.val(kunde.id);
    $results.empty();
  });

  // Tastatursteuerung
  $input
  .off("keydown keyup")
  .on("keydown", function (e) {
    const $items = $results.find(".result-item");

    if (!$items.length) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % $items.length;
      updateSelection($items);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + $items.length) % $items.length;
      updateSelection($items);
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (selectedIndex >= 0) {
        const $selected = $items.eq(selectedIndex);
        $selected.click(); // Trigger Klick
      }
    }
  });

  function updateSelection($items) {
    $items.removeClass("selected");
    const $selected = $items.eq(selectedIndex);
    $selected.addClass("selected");

    // automatisch scrollen
    const container = $results.get(0);
    const item = $selected.get(0);
    if (item && container) {
      const offsetTop = item.offsetTop;
      const offsetBottom = offsetTop + item.offsetHeight;
      const scrollTop = container.scrollTop;
      const containerHeight = container.clientHeight;

      if (offsetTop < scrollTop) {
        container.scrollTop = offsetTop;
      } else if (offsetBottom > scrollTop + containerHeight) {
        container.scrollTop = offsetBottom - containerHeight;
      }
    }
  }

  $(document).on("click", function (e) {
    if (!$(e.target).closest($container).length) {
      $results.empty();
    }
  });
}
// END search as you type


// simplify enter of comments
$(document).on('change', '.x_type', function () {
    var $tr = $(this).closest('tr'); // ganze Zeile finden
    var selectedVal = $(this).val(); // aktueller value
    var selectedText = $(this).find('option:selected').text().trim(); // lesbarer Text
    
    if (selectedVal != 'A' ) {
        $tr.find('.x_remark').val(selectedText); // in x_remark setzen
    } else {
        $tr.find('.x_remark').val(''); // optional: leeren, wenn A/B
    }
});
</script>


<!-- FORM -->
<form id="timesheet_form" method="post" action="<?php echo site_url($this->uri->uri_string()); ?>">
    <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
           value="<?php echo $this->security->get_csrf_hash() ?>">

<table id="x-worktime" class="table table-hover table-bordered table-condensed no-margin" >

<?php 
// get numbers of a month in gregorian calendar
if (!function_exists('days_month')) {
function days_month($month, $year){
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
} 
}
if (!function_exists('my_td')) {
    function my_td($dow) {
        echo '<td'; 
        if ($dow=="Sa") echo ' bgcolor="#c0ff80" ';
        if ($dow=="So") echo ' bgcolor="#ff80c0" ';
        echo '>';
    }
}
if (!function_exists('do_dow')) {
    function do_dow($year, $month, $day) {
        // convert date to day of week
        $date = (string)$year."-".$month."-".$day;
        //Convert the date string into a unix timestamp.
        $unixTimestamp = strtotime($date);
        //Get the day of the week using PHP's date function.
        $dayOfWeek = date("D", $unixTimestamp);
        switch($dayOfWeek){
            case "Mon":
                $dow= "Mo";
                break;
            case "Tue":
                $dow= "Di";
                break;
            case "Wed":
                $dow= "Mi";
                break;
            case "Thu":
                $dow= "Do";
                break;
            case "Fri":
                $dow= "Fr";
                break;
            case "Sat":
                $dow= "Sa";
                break;
            case "Sun":
                $dow= "So";
                break;
            default:
                $dow= "--";
                break;
        }
        return $dow;
    }
}


$worktypes=[
['',    '---'],
['A',    'Arbeitstag'],
['B',    'B&uuml;rotag'],
['D',    'Kundenfahrt'],
['F',    'Feiertag'],
['K',    'Krank'],
['S',    '&Uuml;berstunden'],
['U',    'Urlaub'],
['UU',   'Unbezahlter Urlaub']
];

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

// $user_clients sind gesetzt, mussen ubergeben werden
// alles andere mit den globals
global $day_loop, $dow, $x_from, $x_to, $x_clientid, $x_remark, $x_type, $x_uuid, $x_km;
$my_row_counter = 0; global $my_row_counter;

/* do html table row func */
function do_table_row($user_clients, $worktypes, $did_day = 0) 
{ 
    global $day_loop, $dow, $x_from, $x_to, $x_clientid, $x_remark, $x_type, $x_uuid, $x_km;
    global $my_row_counter;
    $my_row_counter ++;

    // find client in List
    $cur_client = "";
    foreach($user_clients as $u) {
        if ($u->client_id == $x_clientid) { 
            $cur_client = trim($u->client_name) ." ". trim($u->client_surname)." (".$u->customer_no.")";
            break;
        }
    }

    // fallback only clientid
    if  ($cur_client=="" && $x_clientid > 0) 
        $cur_client = $x_clientid;

/* 
 * table row start  
 */
    // tag schon angefangen? oder weitere eintra"ge im gleichen tag?
    if ($did_day == 0) { 
        // bei einfuegen nur 1. zeile id day_loop, sonst eine andere, irgendwie hochzaehlen, sonst geht das nicht
        // und anderen style zur abtrennung
        echo ' <tr class="timestuff" id="tr' . $day_loop . '" style="border-top: 2px solid #888;">';
    } else { 
        echo ' <tr class="timestuff" id="tr' . $day_loop . '_'.$my_row_counter.'">';
    }
    ?>

<!-- tools -->
            <td class="button-cell">
                <?php if ($did_day == 0) {
                // aufwandiger insert button mit clone logic
                ?>
                    <button type="button" name="button<?php echo $day_loop; ?>" id="button<?php echo $day_loop; ?>" 
                        class="btn-ins button" title="Zeile einfügen.">
                      <i class="fa fa-plus"></i>
                    </button>

                    <script>
                        //  timestamp = Math.floor(Date.now() / 1000)
                        // variable counting insertions of this row for changing id
                        var m<?= $day_loop ?>=0;
                        $("#button<?= $day_loop ?>").click(function()
                        {
                            // grab row
                            var $lastRow = $("[id$=tr<?= $day_loop ?>]");

                            // clone it
                            var $newRow = $lastRow.clone();

                            // change id of cloned row
                            $newRow.attr("id","tr<?= $day_loop ?>_" + m<?= $day_loop ?>);
                            //console.log("id: tr<?= $day_loop ?>_m<?= $day_loop ?>");

                            // strich weg
                            $newRow.css('border-top', '');

                            // remove sum id + val from this inserted row
                            $newRow.find("td.sum").empty(); 
                            $newRow.find(".sum").attr('id','sum-DYNAMIC' + m<?= $day_loop ?>);
                            $newRow.find(".x_from").val("00:00");
                            $newRow.find(".x_to").val("00:00");
                            $newRow.find(".x_hours").val("00:00");
                            let uuid = UUID();
                            $newRow.find(".x_uuid").val(uuid);

                            // change search as you type ids - if you change DYNAMIC here, also look into Ajax controller!
                            $newRow.find(".searchInput").attr("id", "x_customer-DYNAMIC" + m<?= $day_loop ?>);
                            $newRow.find(".kundenId").attr("name",  "x_customer-DYNAMIC" + m<?= $day_loop ?>);
                            setupAutocomplete($newRow);

                            // increment
                            m<?= $day_loop ?> ++;

                            //console.log("M: " + m<?= $day_loop ?>);

                            //clear out textbox values
                            $newRow.find(":text").val("");

                            // find insert button
                            const $insertBtn = $newRow.find("button.btn-ins");

                            // Neuen Delete-Button erstellen
                            const $deleteBtn = $("<button>", {
                                title: "Zeile löschen!",
                                type: "button",
                                class: "btn-del button",
                                name: $insertBtn.attr("name").replace("button", "delete"),
                                id: $insertBtn.attr("id").replace("button", "delete"),
                                click: function () {
                                    if (confirm("Diese Zeile wirklich löschen?")) {
                                        let del_uuid = $(this).parent().parent().find(".x_uuid").val();
                                        sendWorktimeDelete(del_uuid);
                                        $(this).parent().parent().remove();
                                    }
                                }
                            }).append($("<i>",{ class: "fa fa-trash" })) ;

                            // Insert-Button ersetzen durch delete button
                            $insertBtn.replaceWith($deleteBtn);

                            //add the new row
                            $lastRow.after($newRow);
                        });
                    </script>
                <?php } else { ?>
                        <button type="button" name="button<?php echo $day_loop."_".$did_day; ?>" 
                            id="button<?php echo $day_loop."_".$did_day; ?>"
                            class="btn-del button" title="Zeile löschen!">
                            <i class="fa fa-trash"></i>
                        </button>
                        <script>
                            $("#button<?= $day_loop."_".$did_day ?>").click(function() {
                            if (confirm("Diese Zeile wirklich löschen?")) {
                                        let del_uuid = $(this).parent().parent().find(".x_uuid").val();
                                        sendWorktimeDelete(del_uuid);
                                        $(this).parent().parent().remove();
                                }
                            });
                        </script>
                <?php } ?>

                <button type="button" class="btn-reset button" title="Werte löschen!" >
                  <i class="fa fa-eraser"></i>
                </button>

                </td>
<!-- END tools -->

<?php
    my_td($dow); 
    echo $dow . ",&nbsp;". $day_loop ."."; 
    ?>

<!-- day -->
    <input class="x_day form-control" name="x_day" type="hidden" value="<?= $day_loop ?>" >
<!-- uuid -->
<?php
if (empty($x_uuid)) $x_uuid = gen_uuid();
?>
    <input class="x_uuid form-control" name="x_uuid" type="hidden" value="<?= $x_uuid ?>" >
    </td>
<?php $x_uuid = ""; ?>

<?php $x_uuid = ""; // sonst bleibt die gleiche vielleicht ueberall stehen, wenn keine in der db :-(?>
<!-- type -->
    <td>
        <select id="x_type-<?= $day_loop ?>" name="x_type" class="x_type form-control" >
        <?php foreach ($worktypes as $t) { ?>
            <option value="<?php echo $t[0]; ?>" <?php if ($t[0] == $x_type) echo ' selected="selected"';?> > <?php echo $t[1]; ?>
            </option>
        <?php } ?>
        </select>
    </td>

<!-- time stuff -->
    <td>
        <input class="x_from timeInput form-control" name="x_from" type="time" value="<?= $x_from ?>">
        <div class="timePicker pickerStart"></div>
    </td>

    <td>
        <input class="x_to timeInput form-control" name="x_to" type="time" value="<?= $x_to ?>">
        <div class="timePicker pickerEnd"></div>
    </td>

    <td >
        <input class="x_hours timeInput form-control" name="x_hours" type="time" value="00:00" >
        <div class="timePicker pickerDuration"></div>
    </td>

        <?php 
        // spalte fuer summe nur einmal am tag
        if ($did_day == 0) {
            echo '<td class="sum" id="sum_'.$day_loop.'" style="color: green; font-weight:bold;"></td>';
        } else { 
            echo '<td></td>';
        }
    ?>

<!-- customer -->
        <td>
            <div id="div_x_customer-<?= $my_row_counter ?>" >
            <?php  /* diese IDs hier innen mussen SO bleiben wegen AJAX save - fix everything someday */ ?>
            <input type="text" class="searchInput" id="x_customer" name="x_customer" value="<?= $cur_client ?>" 
               placeholder="Kundenname..." autocomplete="off" style="width: 22em;">
            <input type="hidden" name="x_customer_id" class="kundenId" value="<?= $x_clientid ?>">
            <div class="results"></div>
            </div>

            <script>
            setupAutocomplete($("#div_x_customer-<?= $my_row_counter ?>"));
            </script>
        </td>

<!-- remark -->
        <td>
            <input name="x_km" type="text" class="x_km form-control" value="<?= $x_km ?>">
        </td>
        <td>
            <input name="x_remark" type="text" class="x_remark form-control" value="<?= $x_remark ?>">
        </td>
</tr>

<?php 
} 
/* end do table row */


/***
 *
 * Main Row Loop 
 *
 */

// nur 1 debug chrissie
//for ($day_loop = 1; $day_loop <= 1 ; $day_loop++) { 
for ($day_loop = 1; $day_loop <=days_month($month, $year); $day_loop++) { 

$dow = do_dow($year, $month, $day_loop);


    // U"berschrift immer am Anfang und sonst vor jedem Montag
    if ($day_loop == 1 || $dow == "Mon") {
        ?>
            <thead>
            <tr style="border-top: 2px solid #888;">
            <th style="width: 1em;"><i class="fa fa-tasks"></i></th>
            <th style="width: 2em;"><?= trans('day'); ?></th>
            <th style="width: 10em;"><?= trans('type'); ?></th>
            <th style="width: 5em;"><?= trans('begin'); ?></th>
            <th style="width: 5em;"><?= trans('end'); ?></th>
            <th style="width: 3em;"><?= trans('hours'); ?></th>
            <th style="width: 3em;"><?= trans('sum'); ?></th>
            <th style="width: 10em;"><?= trans('client'); ?></th>
            <th style="width: 7em;"><?= trans('km'); ?></th>
            <th style="width: 10em;"><?= trans('remark'); ?></th>
            </tr>
            </thead>
            <?php } 

    $x_from="00:00";
    $x_to="00:00";
    $x_clientid=0;
    $x_remark="";
    $x_km=0;
    $x_type="";
    $did_day = 0;

    // befuellte row mit vorhandenen daten
    foreach ($timesheets as $t) {
        if ($t->timesheet_day == $day_loop) {
            $x_from = date("H:i", strtotime($t->timesheet_start));
            $x_to = date("H:i", strtotime($t->timesheet_end));
            $x_clientid = $t->timesheet_clientid;
            $x_remark = $t->timesheet_remark;
            $x_km   = $t->timesheet_km;
            $x_type = $t->timesheet_type;
            $x_uuid = $t->timesheet_uuid;

            do_table_row($user_clients, $worktypes, $did_day);
            $did_day++;
        } 
    }
    // leere row
    if ($did_day == 0)
        do_table_row($user_clients, $worktypes);
    ?>

<?php 
} 
/* END main row loop */

?>
</table>

<br />
<!-- // -->
    <a href="#" class="btn btn-sm btn-primary btn_calc_worktime"> <i class="fa fa-calendar"></i><?= trans('calculate') ?> </a>
    <a href="#" class="btn btn-sm btn-primary btn_check_worktime"> <i class="fa fa-check"></i><?= trans('check') ?> </a>
    <a href="#" class="btn btn-sm btn-success btn_save_worktime"> <i class="fa fa-check"></i><?= trans('save') ?> </a>
<!-- // -->

</form>

<br /> <br />
</div>
</div>

<?php
// vim: set ts=4 sw=4 sts=4 et :
?>

