<div class="card">
    <div class="card-body">
        <h4>Seed Demo Data</h4>
        <p>This will insert sample data into the following configuration tables:</p>

        <ul id="seed-list">
            <li>Payment Modes</li>
            <li>Currencies</li>
            <li>Contract Types</li>
            <li>Estimate Request Statuses</li>
            <li>Estimate Request Forms</li>
            <li>Customer Groups</li>
            <li>Lead Sources</li>
            <li>Lead Statuses</li>
            <li>Departments</li>
            <li>Ticket Priorities</li>
            <li>Ticket Statuses</li>
            <li>Ticket Services</li>
            <li>Predefined Replies</li>
            <li>Spam Filters</li>
        </ul>

        <p class="text-warning">This will not overwrite existing data. If a table already has records, it will be skipped.</p>

        <button id="run-seed" class="btn btn-primary" onclick="runSeed()">Run Seed</button>
        <div id="seed-result" class="mtop20"></div>
    </div>
</div>

<script>
function runSeed() {
    var btn = document.getElementById('run-seed');
    btn.disabled = true;
    btn.innerHTML = 'Seeding...';

    $.post(admin_url + 'seed_data/run', function(res) {
        var html = '<h5>Results:</h5><table class="table table-bordered"><thead><tr><th>Entity</th><th>Result</th></tr></thead><tbody>';
        for (var key in res.results) {
            var r = res.results[key];
            var msg = r.inserted ? r.inserted + ' inserted' : (r.skipped || 'error');
            html += '<tr><td>' + key.replace(/_/g, ' ') + '</td><td>' + msg + '</td></tr>';
        }
        html += '</tbody></table>';
        document.getElementById('seed-result').innerHTML = html;
        btn.innerHTML = 'Done';
    }, 'json');
}
</script>
