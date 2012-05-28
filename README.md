# IPC plugins, web client and web chat for the IRC Bot Phergie

## Installation

1. Open a shell, change into your phergie root directory and run:  

        git submodule add git@github.com:cisso/ipc-plugins.git Phergie/Plugin/ipc-plugins
Alternatively you can create the directory Phergie/Plugin/ipc-plugins by hand and drop all files there.

1. Edit your Settings.php and make the following additions:

        return array(
            ...
            'plugins' => array(
                'WebClient',
                'WebChat',
                ...
            ),
            'plugins.paths' => array(
              dirname(__FILE__) . '/Phergie/Plugin/ipc-plugins' => 'Phergie_Plugin_'
            ),
            'webclient.address' => '127.0.0.1:8123',
            ...
        );