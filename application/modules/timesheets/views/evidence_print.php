    <style>
        .dual-listbox {
            display: flex;
            gap: 20px;
        }
        .dual-select {
            width: 250px;
            height: 300px;
        }
        .list-controls {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }
        #search {
            margin-bottom: 10px;
            width: 250px;
        }
    </style>

<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('print_timesheet'); ?></h1>
</div>

<div id="content">
    <div class="row">
        <?php $this->layout->load_view('layout/alerts'); ?>
<div class="col-xs-8 col-md-8">

<!-- only one customer direct print -->
            <?php if (isset($client_fullname)):?>
                <?= _trans('client'); ?>
                <br />
                <?= $customer_no; ?>
                <br />
                <?= $client_fullname; ?>
                <br />
                <?= $client_street; ?>
                <br />
                <?= $client_city; ?>
                <?= $client_zip; ?>
            <?php endif ; ?>
    </div>
    </div>

    <div class="panel-body">

        <form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>" >
            <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                               value="<?php echo $this->security->get_csrf_hash() ?>">

<table>
<tr><td>
            <label for="year"><?= _trans('year') ?></label>
            <select id="my_year" name="my_year">
                <?php  foreach ($all_year as $y) {
                    echo '<option value="'.$y.'"';
                    if ($year == $y) echo ' selected="selected" ';
                    echo ' >'.$y.'</option>';
                    echo "\n";
                }
                ?>
            </select>

            <br>
Von:
            <label for="month"><?= _trans('month') ?></label>
            <select id="my_month" name="my_month">
                <?php $i=1; foreach ($all_month as $m) {
                    echo '<option value="'.$i.'"';
                    if ($month == $i) echo ' selected="selected" ';
                    echo ' >'.$m.'</option>';
                    echo "\n";
                    $i++; 
                }
                ?>
            </select>

            <br>
Bis:
            <label for="to_month"><?= _trans('month') ?></label>
            <select id="to_month" name="to_month">
                <?php $i=1; foreach ($all_month as $m) {
                    echo '<option value="'.$i.'"';
                    if ($to_month == $i) echo ' selected="selected" ';
                    echo ' >'.$m.'</option>';
                    echo "\n";
                    $i++; 
                }
                ?>
            </select>

</td><td>
&nbsp; &nbsp; &nbsp; &nbsp; <input type="submit" class="btn btn-success" name="btn_submit_blank" value="<?php _trans('blank_generate'); ?>">
</td></tr></table>

            <br /><br />
            <label for="users">Clients von User:</label>
            <select id="users" name="users">
            <option value="0">--All Users--</option>
            <?php foreach ($users as $u): ?>
<option value="<?= $u->user_id ?>"
<?php if($u->user_id == $user_id) echo ' selected = "selected" ';?>>
<?= $u->user_name ?></option>
            <?php endforeach; ?>
            </select> 
            <input type="submit" class="btn btn-success" name="btn_change_user" value="<?php _trans('change_user'); ?>">


<!-- clients select dual listbox -->
        <?php if (!isset($client_fullname)):?>

<br><br>

    <label for="search">Suche Client:</label><br>

<div style="position: relative; width: 250px; margin-bottom: 10px;">
    <input type="text" id="search" placeholder="Suche..." style="width: 100%; padding-right: 28px;">
    <i id="clear-search" class="fa fa-times-circle"
       title="Zurücksetzen"
       style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
              cursor: pointer; display: none; color: #999;"></i>
</div>

    <div class="dual-listbox">
        <!-- Verfügbare Clients -->
        <select class="dual-select" id="clients-left" size="15" multiple>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c->client_id ?>"><?= $c->client_fullname ?> | <?=$c->customer_no ?></option>
            <?php endforeach; ?>
            </select> 
        <div class="list-controls">
            <button type="button" id="add"> &gt;&gt; </button>
            <button type="button" id="remove"> &lt;&lt; </button>
<br /><br />
    <button type="button" id="add-all">» Alle</button>
 <button type="button" id="remove-all">Alle «</button>

        </div>

        <!-- Ausgewählte Clients -->
        <select class="dual-select" id="clients-right" name="clients[]" size="15" multiple></select>
    </div>
<script>
$(document).ready(function() {

// Enter-Taste im Suchfeld verhindert Submit
$("#search").on("keydown", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
    }
});

// Sucheingabe uberwachen und Clear-Button anzeigen/verstecken
$("#search").on("input", function() {
    $("#clear-search").toggle($(this).val().length > 0);
}).on("keydown", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
    }
});

// Clear-Button klick
$("#clear-search").on("click", function() {
    $("#search").val('');
    $("#clients-left option").show();
    $(this).hide();
});

    // Search as you type
    $("#search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#clients-left option").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Move to right
    $("#add").click(function() {
        $("#clients-left option:selected").each(function() {
            $(this).remove().appendTo("#clients-right");
        });
    });

    // Move to left
    $("#remove").click(function() {
        $("#clients-right option:selected").each(function() {
            $(this).remove().appendTo("#clients-left");
        });
    });

// Alle nach rechts verschieben
$("#add-all").click(function() {
    $("#clients-left option").each(function() {
        $(this).remove().appendTo("#clients-right");
    });
});

// Alle nach links verschieben
$("#remove-all").click(function() {
    $("#clients-right option").each(function() {
        $(this).remove().appendTo("#clients-left");
    });
});

    // On submit, select all right-side options
    $("form").submit(function() {
        $("#clients-right option").prop("selected", true);
    });
});
</script>

        <?php endif; ?>

            <br />
            <input type="submit" class="btn btn-success" name="btn_submit" value="<?php _trans('generate'); ?>">
            </form>
        </div>
        </div>
    </div>
</div>
