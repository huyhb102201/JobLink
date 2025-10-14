<script>
    window.authId = {{ auth()->id() ?? 'null' }};
    window.currentPartnerId = null;
    window.currentJobId = null;
    window.currentOrgId = null;
    window.presenceInitialized = false;
</script>
