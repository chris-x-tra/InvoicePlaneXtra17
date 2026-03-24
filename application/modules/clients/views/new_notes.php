
<div id="headerbar">
    <h1 class="headerbar-title">
<?php if ($new == 1): ?>
    Neue Notizen
<?php else: ?>
    die letzten 100 Notizen
<?php endif; ?>
    </h1>
</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-5">

<?php if ($new == 1): ?>
    <h3>Neue Notizen</h3>
    <br />
    <a href="<?php echo site_url('clients/new_notes_mark_read'); ?>">alle als gelesen markieren</a>
    <br />
<?php else: ?>
    <h3>die letzten 100 Notizen</h3>
<?php endif; ?>

<hr />
<style>
    .note-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .note-card {
        background: #fffef0; /* Klassisches Post-it Gelbweiß */
        border-left: 5px solid #ffcc00; /* Akzentleiste */
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
        position: relative;
        min-height: 150px;
        transition: transform 0.2s;
    }

    .note-card:hover {
        transform: translateY(-5px);
    }

    .note-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .note-date {
        color: #666;
        font-weight: bold;
    }

    .client-link {
        text-decoration: none;
        color: #007bff;
        background: #e7f1ff;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .client-link:hover {
        background: #007bff;
        color: white;
    }

    .client-name {
        display: block;
        font-weight: 700;
        margin-bottom: 5px;
        color: #333;
    }

    .note-content {
        line-height: 1.6;
        color: #444;
    }
</style>

<div class="note-container">
    <?php foreach ($notes as $n): ?>
        <div class="note-card">
            <div class="note-header">
                <div>
                    <span class="note-date"><?= date('d.m.Y', strtotime($n['client_note_date'])) ?></span>
                    <span class="client-name"><?= htmlspecialchars($n_clients[$n['client_id']]) ?></span>
                </div>
                <a href="<?= site_url('clients/view/'.$n['client_id']); ?>" class="client-link">
                    Zum Kunden &rarr;
                </a>
            </div>
            
            <div class="note-content">
                <?= nl2br(htmlspecialchars($n['client_note'])) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
        </div>
    </div>
</div>

<?php
/*
 * vim: tabstop=4 shiftwidth=4 expandtab
 */
?>
