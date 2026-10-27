<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php

?>
<section class="card narrow">
    <h1><?= e($formTitle) ?></h1>

    <form method="post" action="<?= e($formAction) ?>" data-validate novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="title">Event Name <span class="req">*</span></label>
            <input type="text" id="title" name="title" maxlength="150" required
                   data-rules="required,min:3,max:150" value="<?= e($values['title']) ?>">
            <small class="field-error" data-for="title"><?= e($errors['title'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" maxlength="2000"
                      data-rules="max:2000"><?= e($values['description']) ?></textarea>
            <small class="field-error" data-for="description"><?= e($errors['description'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="event_date">Date <span class="req">*</span></label>
            <input type="datetime-local" id="event_date" name="event_date" required
                   data-rules="required" value="<?= e($values['event_date']) ?>">
            <small class="field-error" data-for="event_date"><?= e($errors['event_date'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="location">Location <span class="req">*</span></label>
            <input type="text" id="location" name="location" maxlength="150" required
                   data-rules="required,min:2,max:150" value="<?= e($values['location']) ?>">
            <small class="field-error" data-for="location"><?= e($errors['location'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="category">Category <span class="req">*</span></label>
            <select id="category" name="category" required data-rules="required">
                <?php foreach (EVENT_CATEGORIES as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $values['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="field-error" data-for="category"><?= e($errors['category'] ?? '') ?></small>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
            <a class="btn" href="dashboard.php">Cancel</a>
        </div>
    </form>
</section>
