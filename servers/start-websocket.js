const { spawn } = require('child_process');

const php = spawn('C:/xampp/php/php.exe', ['server.php'], {
    stdio: 'inherit',
    cwd: __dirname
});

php.on('close', (code) => {
    console.log(`PHP process exited with code ${code}`);
});