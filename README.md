# TransmissionRPC
Transmission RPC Client

```
<?php

$tr = TransmissionRPC::factory('http://localhost:9091/transmission/rpc/);

$add = $tr->add_metainfo('torrent.torrent', '/pathto');
var_dump ($add)
?>

```
