<?php


    define('SQLiteCredentials', null); // проходит без пароля
    define('SQLiteShowDBList', false); // показывать список баз sqlite найденных

    define('SQLiteBaseDir', dirname(__FILE__));
    define('SQLiteDir', SQLiteBaseDir); // папка где базы sqlite лежат

   // $_GET['db'] = (!isset($_GET['db'])) ? 'db.sqlite' : $_GET['db']; // если нужно чтобы сразу открывал db.dqlite
    
    function adminer_object() {
        class AdminerSoftware extends Adminer {

            function loginForm()
            {
                ob_start();
                parent::loginForm();
                $html = ob_get_clean();

                if (defined('SQLiteShowDBList') && SQLiteShowDBList) {
                    $sqliteDbAsOption = function(){
                        $ret = ['<option>--select db--</option>'];
                        $dbList = glob(SQLiteDir.'/*.sqlite');
                        if ($dbList) foreach($dbList as $dbFile) {
                            $value = str_replace(SQLiteBaseDir.'/','',$dbFile);
                            $option = '<option value="'.$value.'"';
                            if (!empty($_REQUEST['db']) && $_REQUEST['db']==$value) $option .= ' selected="selected"';
                            $option .= '>'.basename($dbFile).'</option>';
                            $ret[] = $option;
                        }
                        return implode($ret);
                    };

                    $injectHtml .= '<select id="sqlite-db-select" onchange="selectSQLiteDb(this.value)" name="db" style="width:100%;">'.$sqliteDbAsOption().'</select>';
                    $html = preg_replace('~<input\sname="auth\[db\]"[^>]+>~', '$0'.$injectHtml, $html);
                }

                echo $html;

                if (defined('SQLiteShowDBList') && SQLiteShowDBList) {
                ?>
                    <script type="text/javascript">
                        function FindByAttributeValue(tag, attribute, value)    {
                            var All = document.getElementsByTagName(tag);
                            for (var i = 0; i < All.length; i++)       {
                                if (All[i].getAttribute(attribute) == value) { return All[i]; }
                            }
                        }

                        function form_el(name) {
                            return FindByAttributeValue('input', 'name', 'auth['+name+']');
                        }

                        function selectSQLiteDb(dbPath) {
                            if (dbPath=='--select db--') dbPath = '';
                            var el = FindByAttributeValue('input', 'name', 'auth[db]')
                            el.value = dbPath;
                        }

                        window.onload = function(e) {

                            var el = FindByAttributeValue('select', 'name', 'auth[driver]');

                            el.onchange = function(){
                                var sqliteDbEl = document.getElementById('sqlite-db-select');
                                form_el('server').parentElement.parentElement.style.display = "none";
                                if(this.value == 'sqlite' || this.value == 'sqlite2') {
                                    <?php if (!SQLiteCredentials):?>
                                        form_el('username').parentElement.parentElement.style.display = "none";
                                        form_el('password').parentElement.parentElement.style.display = "none";
                                    <?php endif;?>
                                    form_el('db').style.display = "none";
                                    sqliteDbEl.style.display = "block";
                                } else {
                                    form_el('server').parentElement.parentElement.style.display = "table-row";
                                    <?php if (!SQLiteCredentials):?>
                                        form_el('username').parentElement.parentElement.style.display = "table-row";
                                        form_el('password').parentElement.parentElement.style.display = "table-row";
                                    <?php endif;?>
                                    form_el('db').style.display = "block";
                                    sqliteDbEl.style.display = "none";
                                }
                            };

                            el.onchange();
                        };

                    </script>
                <?php
                }
            }

            function login($login, $password) {
                $ret = false;

                $sqlite = (isset($_REQUEST['auth']['db']) && $_REQUEST['auth']['db']=='sqlite')
                          || (isset($_REQUEST['sqlite']))
                ;

                if ($sqlite) {
                    if (!SQLiteCredentials) {
                        $ret = true;
                    } else {
                        $ret = ( ($login.':'.$password) == SQLiteCredentials );
                    }
                } else {
                    $ret = parent::login($login, $password);
                }

                return $ret ;
            }
        }
        return new AdminerSoftware;
    }