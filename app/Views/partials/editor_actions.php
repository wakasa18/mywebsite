<div class="editor-actions" role="group" aria-label="Form actions">
    <a href="<?= esc($cancelUrl) ?>" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary" data-busy-label="Saving…">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
        <?= esc($submitLabel) ?>
    </button>
</div>
