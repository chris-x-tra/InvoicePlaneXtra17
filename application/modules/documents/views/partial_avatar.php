<?php
/*
 * Profilbild eines Users. Benötigt: $user_id, optional $avatar_size (px, default 120)
 * Einbinden: <?php $this->load->view('documents/partial_avatar', ['user_id' => $user->user_id]); ?>
 */
$avatar_size = $avatar_size ?? 120;
?>
<div class="user-avatar" style="margin-bottom:15px;">
    <img src="<?php echo site_url('documents/avatar/' . (int) $user_id); ?>"
         alt="" width="<?php echo (int) $avatar_size; ?>" height="<?php echo (int) $avatar_size; ?>"
         style="object-fit:cover;border-radius:50%;background:#eee;"
         onerror="this.style.visibility='hidden'">
    <div style="margin-top:8px;">
        <a href="<?php echo site_url('documents/upload/user/' . (int) $user_id . '/avatar'); ?>" class="btn btn-xs btn-default">
            <i class="fa fa-camera"></i> Profilbild ändern
        </a>
    </div>
</div>
