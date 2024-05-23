<?php

class SSHMysql
{

    private $_server;

    function __construct()
    {
        $env = parse_ini_file('.env');
        $this->_server = [];
        $this->_server['sshipaddress'] = $env['SSH_IPADDRESS'];
        $this->_server['sshport'] = $env['SSH_PORT'];
        $this->_server['sshusername'] = $env['SSH_USERNAME'];
        $this->_server['sshpubkeyfile'] = $env['SSH_PUBKEYFILE'];
        $this->_server['sshprivkeyfile'] = $env['SSH_PRIVKEYFILE'];
        $this->_server['sshpassphrase'] = $env['SSH_PASSPHRASE'];
        $this->_server['mysqlipaddress'] = $env['MYSQL_IPADDRESS'];
        $this->_server['mysqlusername'] = $env['MYSQL_USERNAME'];
        $this->_server['mysqlpassword'] = $env['MYSQL_PASSWORD'];
        $this->_server['mysqlport'] = $env['MYSQL_PORT'];
    }

    public function query($sql)
    {
        // if !ssh2_connect, exit because the SSH2 module is not installed for PHP //
        $_server = $this->_server;
        if (function_exists("ssh2_connect")) {
            $connection = ssh2_connect($_server['sshipaddress'], $_server['sshport']);

            // if the SSH username and password are correct, try to run the query via ssh2_exec; if it's not correct return authentication failure to user //
            if (ssh2_auth_pubkey_file($connection, $_server['sshusername'], $_server['sshpubkeyfile'], $_server['sshprivkeyfile'], $_server['sshpassphrase'])) {
                // set up a shell script.  use port-forwarding over 3307 to tunnel into the remote database via SSH, this COULD CHANGE depending on your server's SSH configuration.  3307 is most common.//
                // clean up the SQL query so things like slashes, single quotes and double quotes don't cause errors //
                $ssh_query = 'ssh -L 3307:'.$_server['sshipaddress'].':'.$_server['mysqlport'].'; echo "'.str_replace('"', '\'', stripslashes($sql)).'" | mysql -u '.$_server['mysqlusername'].' -h '.$_server['mysqlipaddress'].' --password='.$_server['mysqlpassword'];

                // execute the query over a secure connection //
                $result = ssh2_exec($connection, $ssh_query);

                // catch any stream errors that might occur.  This will return the command line's MySQL errors to help with query debugging if there's an error in the SQL statement
                $error_result = ssh2_fetch_stream($result, SSH2_STREAM_STDERR);

                // turn on stream blocking to save the query results and errors to variables
                stream_set_blocking($result, true);
                stream_set_blocking($error_result, true);

                // parse the sql query. All results come back as strings within a standard, tab delimited format that can be split into result sets
                $arr_1 = explode("\n", stream_get_contents($result));

                $keys = explode("\t", $arr_1[0]);  // get the column names
                $results = [];

                for ($i = 1; $i < (sizeof($arr_1) - 1); $i++) // parse the results
                {
                    $values = explode("\t", $arr_1[$i]);
                    $return = new stdClass();
                    $index = 0;
                    foreach ($values as $v) {
                        $return->{$keys[$index]} = $v;
                        $index++;
                    }
                    $results[] = $return;
                }

                if (sizeof($results) > 0) {
                    $response = [
                        'status' => 'success',
                        'msg' => 'DB Query was successful.',
                        'dataset' => $results,
                        'type' => 'ssh',
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'msg' => 'There is an error in your SQL statement, or your sql returned no results',
                        'errorset' => stream_get_contents($error_result),
                        'dataset' => [],
                        'type' => 'ssh',
                    ];
                }

                // close the SSH tunnel
                fclose($result);
                if (function_exists('ssh2_disconnect')) {
                    ssh2_disconnect($connection);
                } else { // if no disconnect func is available, close conn, unset var
                    fclose($connection);
                    $connection = false;
                }

                return $response;
            } else {
                return [
                    'status' => 'error',
                    'msg' => 'SSH Authentication Failed because of a bad username or password. Please check the SSH authentication settings and try again.',
                    'dataset' => [],
                    'type' => 'ssh',
                ];
            }
        } else {
            return [
                'status' => 'error',
                'msg' => 'SSH Authentication Failed because SSH2 Library is not installed on this server.<br/><br/><b>SSH2 Library is required for making SSH connections to remote servers.</b>',
                'dataset' => [],
                'type' => 'ssh',
            ];
        }
    }

}