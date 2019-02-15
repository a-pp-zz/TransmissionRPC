<?php
namespace AppZz\Http;

use AppZz\Helpers\Arr;
use AppZz\Http\TransmissionRPC\Exception as Exception;

/**
 * Class TransmissionRPC
 * @package AppZz\Http
 * @version 1.0.2
 * @author CoolSwitcher
 * @team AppZz
 */
class TransmissionRPC {

	private $_session_id;
	private $_username;
	private $_password;
	private $_endpoint;
	private $_timezone = 'Europe/Moscow';
	private $_datefmt = 'd.m.Y @ H:i:s';

	private $_populate = [
		'status'
	];

	const UA = 'AppZz Transmission Client/1.0';

	/*
	 * Transmission Statuses
	 */
	const TR_STATUS_STOPPED        = 0; /* Torrent is stopped */
	const TR_STATUS_CHECK_WAIT     = 1; /* Queued to check files */
	const TR_STATUS_CHECK          = 2; /* Checking files */
	const TR_STATUS_DOWNLOAD_WAIT  = 3; /* Queued to download */
	const TR_STATUS_DOWNLOAD       = 4; /* Downloading */
	const TR_STATUS_SEED_WAIT      = 5; /* Queued to seed */
	const TR_STATUS_SEED           = 6; /* Seeding */

	const TR_DEFAULT_HOST = 'localhost';
	const TR_DEFAULT_PORT = 9091;
	const TR_DEFAULT_PATH = '/transmission/rpc/';

	/**
	 * TransmissionRPC constructor.
	 * @param string|array $input
	 * @throws Exception
	 */
	public function __construct ($input = NULL)
	{
		if (is_array ($input)) {

			$secure = Arr::get ($input, 'secure', FALSE);
			$host   = Arr::get ($input, 'host', TransmissionRPC::TR_DEFAULT_HOST);
			$port   = Arr::get ($input, 'port', TransmissionRPC::TR_DEFAULT_PORT);
			$path   = Arr::get ($input, 'path', TransmissionRPC::TR_DEFAULT_PATH);

		} else if (is_string ($input)) {

			$p      = parse_url ($input);
			$secure = (Arr::get($p, 'scheme') === 'https');
			$host   = Arr::get($p, 'host', TransmissionRPC::TR_DEFAULT_HOST);
			$port   = Arr::get($p, 'port');
			$path   = Arr::get($p, 'path', TransmissionRPC::TR_DEFAULT_PATH);

			if ( ! $port AND $secure) {
				$port = 443;
			} else {
				$port = TransmissionRPC::TR_DEFAULT_PORT;
			}

		} else {
			throw new Exception('Wrong input arguments!', Exception::E_INVALIDARGS);
		}

		if ($path) {
			$path = '/' . trim ($path, '/') . '/';
		}

		$this->_endpoint = sprintf ('http%s://%s:%d%s', ($secure ? 's' : ''), $host, $port, $path);
	}

	public static function factory ($input)
	{
		return new TransmissionRPC ($input);
	}

	/**
	 * Set username and password
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	public function auth ($username = '', $password = '')
	{
		if ($username) {
			$this->_username = $username;
		}

		if ($password) {
			$this->_password = $password;
		}

		return $this;
	}

	/**
	 * Set timezone
	 * @param string $tz
	 * @return $this
	 */
	public function timezone ($tz = '')
	{
		if ($tz) {
			$this->_timezone = $tz;
		}

		return $this;
	}

	/**
	 * Set date format
	 * @param string $fmt
	 * @return $this
	 */
	public function date_format ($fmt = '')
	{
		if ($fmt) {
			$this->_datefmt = $fmt;
		}

		return $this;
	}

	/**
	 * @param bool|array $fields
	 * @return $this
	 */
	public function populate ($fields = FALSE)
	{
		$this->_populate = (array) $fields;
		return $this;
	}

	/**
	 * Get torrents list
	 *
	 * Available fields:
	 *
	 * key                         | type                        | source
	 * ----------------------------+-----------------------------+---------
	 * activityDate                | number                      | tr_stat
	 * addedDate                   | number                      | tr_stat
	 * bandwidthPriority           | number                      | tr_priority_t
	 * comment                     | string                      | tr_info
	 * corruptEver                 | number                      | tr_stat
	 * creator                     | string                      | tr_info
	 * dateCreated                 | number                      | tr_info
	 * desiredAvailable            | number                      | tr_stat
	 * doneDate                    | number                      | tr_stat
	 * downloadDir                 | string                      | tr_torrent
	 * downloadedEver              | number                      | tr_stat
	 * downloadLimit               | number                      | tr_torrent
	 * downloadLimited             | boolean                     | tr_torrent
	 * error                       | number                      | tr_stat
	 * errorString                 | string                      | tr_stat
	 * eta                         | number                      | tr_stat
	 * etaIdle                     | number                      | tr_stat
	 * files                       | array (see below)           | n/a
	 * fileStats                   | array (see below)           | n/a
	 * hashString                  | string                      | tr_info
	 * haveUnchecked               | number                      | tr_stat
	 * haveValid                   | number                      | tr_stat
	 * honorsSessionLimits         | boolean                     | tr_torrent
	 * id                          | number                      | tr_torrent
	 * isFinished                  | boolean                     | tr_stat
	 * isPrivate                   | boolean                     | tr_torrent
	 * isStalled                   | boolean                     | tr_stat
	 * leftUntilDone               | number                      | tr_stat
	 * magnetLink                  | string                      | n/a
	 * manualAnnounceTime          | number                      | tr_stat
	 * maxConnectedPeers           | number                      | tr_torrent
	 * metadataPercentComplete     | double                      | tr_stat
	 * name                        | string                      | tr_info
	 * peer-limit                  | number                      | tr_torrent
	 * peers                       | array (see below)           | n/a
	 * peersConnected              | number                      | tr_stat
	 * peersFrom                   | object (see below)          | n/a
	 * peersGettingFromUs          | number                      | tr_stat
	 * peersSendingToUs            | number                      | tr_stat
	 * percentDone                 | double                      | tr_stat
	 * pieces                      | string (see below)          | tr_torrent
	 * pieceCount                  | number                      | tr_info
	 * pieceSize                   | number                      | tr_info
	 * priorities                  | array (see below)           | n/a
	 * queuePosition               | number                      | tr_stat
	 * rateDownload (B/s)          | number                      | tr_stat
	 * rateUpload (B/s)            | number                      | tr_stat
	 * recheckProgress             | double                      | tr_stat
	 * secondsDownloading          | number                      | tr_stat
	 * secondsSeeding              | number                      | tr_stat
	 * seedIdleLimit               | number                      | tr_torrent
	 * seedIdleMode                | number                      | tr_inactvelimit
	 * seedRatioLimit              | double                      | tr_torrent
	 * seedRatioMode               | number                      | tr_ratiolimit
	 * sizeWhenDone                | number                      | tr_stat
	 * startDate                   | number                      | tr_stat
	 * status                      | number                      | tr_stat
	 * trackers                    | array (see below)           | n/a
	 * trackerStats                | array (see below)           | n/a
	 * totalSize                   | number                      | tr_info
	 * torrentFile                 | string                      | tr_info
	 * uploadedEver                | number                      | tr_stat
	 * uploadLimit                 | number                      | tr_torrent
	 * uploadLimited               | boolean                     | tr_torrent
	 * uploadRatio                 | double                      | tr_stat
	 * wanted                      | array (see below)           | n/a
	 * webseeds                    | array (see below)           | n/a
	 * webseedsSendingToUs         | number                      | tr_stat
	 * 							|                             |
	 * 							|                             |
	 * -------------------+--------+-----------------------------+
	 * files              | array of objects, each containing:   |
	 *                    +-------------------------+------------+
	 *                    | bytesCompleted          | number     | tr_torrent
	 *                    | length                  | number     | tr_info
	 *                    | name                    | string     | tr_info
	 * -------------------+--------------------------------------+
	 * fileStats          | a file's non-constant properties.    |
	 *                    | array of tr_info.filecount objects,  |
	 *                    | each containing:                     |
	 *                    +-------------------------+------------+
	 *                    | bytesCompleted          | number     | tr_torrent
	 *                    | wanted                  | boolean    | tr_info
	 *                    | priority                | number     | tr_info
	 * -------------------+--------------------------------------+
	 * peers              | array of objects, each containing:   |
	 *                    +-------------------------+------------+
	 *                    | address                 | string     | tr_peer_stat
	 *                    | clientName              | string     | tr_peer_stat
	 *                    | clientIsChoked          | boolean    | tr_peer_stat
	 *                    | clientIsInterested      | boolean    | tr_peer_stat
	 *                    | flagStr                 | string     | tr_peer_stat
	 *                    | isDownloadingFrom       | boolean    | tr_peer_stat
	 *                    | isEncrypted             | boolean    | tr_peer_stat
	 *                    | isIncoming              | boolean    | tr_peer_stat
	 *                    | isUploadingTo           | boolean    | tr_peer_stat
	 *                    | isUTP                   | boolean    | tr_peer_stat
	 *                    | peerIsChoked            | boolean    | tr_peer_stat
	 *                    | peerIsInterested        | boolean    | tr_peer_stat
	 *                    | port                    | number     | tr_peer_stat
	 *                    | progress                | double     | tr_peer_stat
	 *                    | rateToClient (B/s)      | number     | tr_peer_stat
	 *                    | rateToPeer (B/s)        | number     | tr_peer_stat
	 * -------------------+--------------------------------------+
	 * peersFrom          | an object containing:                |
	 *                    +-------------------------+------------+
	 *                    | fromCache               | number     | tr_stat
	 *                    | fromDht                 | number     | tr_stat
	 *                    | fromIncoming            | number     | tr_stat
	 *                    | fromLpd                 | number     | tr_stat
	 *                    | fromLtep                | number     | tr_stat
	 *                    | fromPex                 | number     | tr_stat
	 *                    | fromTracker             | number     | tr_stat
	 * -------------------+--------------------------------------+
	 * pieces             | A bitfield holding pieceCount flags  | tr_torrent
	 *                    | which are set to 'true' if we have   |
	 *                    | the piece matching that position.    |
	 *                    | JSON doesn't allow raw binary data,  |
	 *                    | so this is a base64-encoded string.  |
	 * -------------------+--------------------------------------+
	 * priorities         | an array of tr_info.filecount        | tr_info
	 *                    | numbers. each is the tr_priority_t   |
	 *                    | mode for the corresponding file.     |
	 * -------------------+--------------------------------------+
	 * trackers           | array of objects, each containing:   |
	 *                    +-------------------------+------------+
	 *                    | announce                | string     | tr_tracker_info
	 *                    | id                      | number     | tr_tracker_info
	 *                    | scrape                  | string     | tr_tracker_info
	 *                    | tier                    | number     | tr_tracker_info
	 * -------------------+--------------------------------------+
	 * trackerStats       | array of objects, each containing:   |
	 *                    +-------------------------+------------+
	 *                    | announce                | string     | tr_tracker_stat
	 *                    | announceState           | number     | tr_tracker_stat
	 *                    | downloadCount           | number     | tr_tracker_stat
	 *                    | hasAnnounced            | boolean    | tr_tracker_stat
	 *                    | hasScraped              | boolean    | tr_tracker_stat
	 *                    | host                    | string     | tr_tracker_stat
	 *                    | id                      | number     | tr_tracker_stat
	 *                    | isBackup                | boolean    | tr_tracker_stat
	 *                    | lastAnnouncePeerCount   | number     | tr_tracker_stat
	 *                    | lastAnnounceResult      | string     | tr_tracker_stat
	 *                    | lastAnnounceStartTime   | number     | tr_tracker_stat
	 *                    | lastAnnounceSucceeded   | boolean    | tr_tracker_stat
	 *                    | lastAnnounceTime        | number     | tr_tracker_stat
	 *                    | lastAnnounceTimedOut    | boolean    | tr_tracker_stat
	 *                    | lastScrapeResult        | string     | tr_tracker_stat
	 *                    | lastScrapeStartTime     | number     | tr_tracker_stat
	 *                    | lastScrapeSucceeded     | boolean    | tr_tracker_stat
	 *                    | lastScrapeTime          | number     | tr_tracker_stat
	 *                    | lastScrapeTimedOut      | boolean    | tr_tracker_stat
	 *                    | leecherCount            | number     | tr_tracker_stat
	 *                    | nextAnnounceTime        | number     | tr_tracker_stat
	 *                    | nextScrapeTime          | number     | tr_tracker_stat
	 *                    | scrape                  | string     | tr_tracker_stat
	 *                    | scrapeState             | number     | tr_tracker_stat
	 *                    | seederCount             | number     | tr_tracker_stat
	 *                    | tier                    | number     | tr_tracker_stat
	 * -------------------+-------------------------+------------+
	 * wanted             | an array of tr_info.fileCount        | tr_info
	 *                    | 'booleans' true if the corresponding |
	 *                    | file is to be downloaded.            |
	 * -------------------+--------------------------------------+
	 * webseeds           | an array of strings:                 |
	 *                    +-------------------------+------------+
	 *                    | webseed                 | string     | tr_info
	 *                    +-------------------------+------------+
	 *
	 * @param array $ids
	 * @param array $fields
	 * @return bool|mixed
	 */
	public function get ($ids = [], array $fields = [])
	{
		$defaults = ['id', 'name', 'status', 'totalSize'];

		if ( ! empty($fields)) {
			$fields = array_merge ($defaults, $fields);
		} else {
			$fields = $defaults;
		}

		$args = [
			'fields' => $fields,
			'ids'    => $ids
		];

		$result = $this->_request ('torrent-get', $args);
		return $this->_result ($result, 'arguments.torrents');
	}

	/**
	 * Start torrent/s
	 * @param array $ids
	 * @return bool|mixed
	 */
	public function start ($ids = [])
	{
		$args = [
			'ids' => $ids
		];

		$result = $this->_request ('torrent-start', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Start (now) torrent/s
	 * @param array $ids
	 * @return bool|mixed
	 */
	public function start_now ($ids = [])
	{
		$args = [
			'ids' => $ids
		];

		$result = $this->_request ('torrent-start-now', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Stop torrent/s
	 * @param array $ids
	 * @return bool|mixed
	 */
	public function stop ($ids = [])
	{
		$args = [
			'ids' => $ids
		];

		$result = $this->_request ('torrent-stop', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Reannounce torrent/s
	 * @param array $ids
	 * @return bool|mixed
	 */
	public function reannounce ($ids = [])
	{
		$args = [
			'ids' => $ids
		];

		$result = $this->_request ('reannounce', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Verify torrent/s
	 * @param array $ids
	 * @return bool|mixed
	 */
	public function verify ($ids = [])
	{
		$args = [
			'ids' => $ids
		];

		$result = $this->_request ('torrent-verify', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Set torrent params
	 *
	 * Available fields:
	 *
	 * string                | value type & description
	 * ----------------------+-------------------------------------------------
	 * "bandwidthPriority"   | number     this torrent's bandwidth tr_priority_t
	 * "downloadLimit"       | number     maximum download speed (KBps)
	 * "downloadLimited"     | boolean    true if "downloadLimit" is honored
	 * "files-wanted"        | array      indices of file(s) to download
	 * "files-unwanted"      | array      indices of file(s) to not download
	 * "honorsSessionLimits" | boolean    true if session upload limits are honored
	 * "ids"                 | array      torrent list, as described in 3.1
	 * "location"            | string     new location of the torrent's content
	 * "peer-limit"          | number     maximum number of peers
	 * "priority-high"       | array      indices of high-priority file(s)
	 * "priority-low"        | array      indices of low-priority file(s)
	 * "priority-normal"     | array      indices of normal-priority file(s)
	 * "queuePosition"       | number     position of this torrent in its queue [0...n)
	 * "seedIdleLimit"       | number     torrent-level number of minutes of seeding inactivity
	 * "seedIdleMode"        | number     which seeding inactivity to use.  See tr_idlelimit
	 * "seedRatioLimit"      | double     torrent-level seeding ratio
	 * "seedRatioMode"       | number     which ratio to use.  See tr_ratiolimit
	 * "trackerAdd"          | array      strings of announce URLs to add
	 * "trackerRemove"       | array      ids of trackers to remove
	 * "trackerReplace"      | array      pairs of <trackerId/new announce URLs>
	 * "uploadLimit"         | number     maximum upload speed (KBps)
	 * "uploadLimited"       | boolean    true if "uploadLimit" is honored
	 * @param array $args
	 * @return bool|mixed
	 */
	public function set ($args = [])
	{
		$result = $this->_request ('torrent-set', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Add torrent
	 *
	 * All arguments:
	 * key                  | value type & description
	 * ---------------------+-------------------------------------------------
	 * "cookies"            | string      pointer to a string of one or more cookies.
	 * "download-dir"       | string      path to download the torrent to
	 * "filename"           | string      filename or URL of the .torrent file
	 * "metainfo"           | string      base64-encoded .torrent content
	 * "paused"             | boolean     if true, don't start the torrent
	 * "peer-limit"         | number      maximum number of peers
	 * "bandwidthPriority"  | number      torrent's bandwidth tr_priority_t
	 * "files-wanted"       | array       indices of file(s) to download
	 * "files-unwanted"     | array       indices of file(s) to not download
	 * "priority-high"      | array       indices of high-priority file(s)
	 * "priority-low"       | array       indices of low-priority file(s)
	 * "priority-normal"    | array       indices of normal-priority file(s)
	 *
	 * @param array $args
	 * @return bool|mixed
	 */
	public function add ($args = [])
	{
		$args = (array) $args;
		$result = $this->_request ('torrent-add', $args);
		$result = $this->_result ($result, 'arguments');

		if (is_array ($result) AND ! empty ($result)) {
			foreach ($result as $key=>&$value) {
				$value['status'] = $key;
			}
		}

		return array_shift ($result);
	}

	/**
	 * @param string $filename	filename or url
	 * @param null $download_dir	download directory
	 * @return bool|mixed
	 */
	public function add_file ($filename = '', $download_dir = NULL)
	{
		$args = [];
		$args['filename'] = $filename;

		if ($download_dir) {
			$args['download-dir'] = $download_dir;
		}

		return $this->add ($args);
	}

	public function add_metainfo ($filename = '', $download_dir = NULL)
	{
		if ( ! file_exists($filename)) {
			throw new Exception('Torrent file not exists');
		}

		$args = [];
		$args['metainfo'] = base64_encode(file_get_contents($filename));

		if ($download_dir) {
			$args['download-dir'] = $download_dir;
		}

		return $this->add ($args);
	}

	/**
	 * Remove torrent/s
	 * @param array $ids
	 * @param bool $delete_local_data
	 * @return bool|mixed
	 */
	public function remove ($ids = [], $delete_local_data = FALSE)
	{
		$args = [
			'ids'               => $ids,
			'delete-local-data' => $delete_local_data
		];

		$result = $this->_request ('torrent-remove', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Move torrent/s data location to another place
	 * @param array $ids
	 * @param $location
	 * @return bool|mixed
	 */
	public function move ($ids = [], $location)
	{
		$args = [
			'ids'      => $ids,
			'location' => $location,
			'move'     => TRUE
		];

		$result = $this->_request ('torrent-set-location', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Rename torrent/s folders/files
	 * @param array $ids
	 * @param $path
	 * @param $name
	 * @return bool|mixed
	 */
	public function rename ($ids = [], $path, $name)
	{
		$args = [
			'ids'  => $ids,
			'path' => $path,
			'name' => $name
		];

		$result = $this->_request ('torrent-rename-path', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Get session variables
	 *
	 * "alt-speed-down"                 | number     | max global download speed (KBps)
	 * "alt-speed-enabled"              | boolean    | true means use the alt speeds
	 * "alt-speed-time-begin"           | number     | when to turn on alt speeds (units: minutes after midnight)
	 * "alt-speed-time-enabled"         | boolean    | true means the scheduled on/off times are used
	 * "alt-speed-time-end"             | number     | when to turn off alt speeds (units: same)
	 * "alt-speed-time-day"             | number     | what day(s) to turn on alt speeds (look at tr_sched_day)
	 * "alt-speed-up"                   | number     | max global upload speed (KBps)
	 * "blocklist-url"                  | string     | location of the blocklist to use for "blocklist-update"
	 * "blocklist-enabled"              | boolean    | true means enabled
	 * "blocklist-size"                 | number     | number of rules in the blocklist
	 * "cache-size-mb"                  | number     | maximum size of the disk cache (MB)
	 * "config-dir"                     | string     | location of transmission's configuration directory
	 * "download-dir"                   | string     | default path to download torrents
	 * "download-queue-size"            | number     | max number of torrents to download at once (see download-queue-enabled)
	 * "download-queue-enabled"         | boolean    | if true, limit how many torrents can be downloaded at once
	 * "dht-enabled"                    | boolean    | true means allow dht in public torrents
	 * "encryption"                     | string     | "required", "preferred", "tolerated"
	 * "idle-seeding-limit"             | number     | torrents we're seeding will be stopped if they're idle for this long
	 * "idle-seeding-limit-enabled"     | boolean    | true if the seeding inactivity limit is honored by default
	 * "incomplete-dir"                 | string     | path for incomplete torrents, when enabled
	 * "incomplete-dir-enabled"         | boolean    | true means keep torrents in incomplete-dir until done
	 * "lpd-enabled"                    | boolean    | true means allow Local Peer Discovery in public torrents
	 * "peer-limit-global"              | number     | maximum global number of peers
	 * "peer-limit-per-torrent"         | number     | maximum global number of peers
	 * "pex-enabled"                    | boolean    | true means allow pex in public torrents
	 * "peer-port"                      | number     | port number
	 * "peer-port-random-on-start"      | boolean    | true means pick a random peer port on launch
	 * "port-forwarding-enabled"        | boolean    | true means enabled
	 * "queue-stalled-enabled"          | boolean    | whether or not to consider idle torrents as stalled
	 * "queue-stalled-minutes"          | number     | torrents that are idle for N minuets aren't counted toward seed-queue-size or download-queue-size
	 * "rename-partial-files"           | boolean    | true means append ".part" to incomplete files
	 * "rpc-version"                    | number     | the current RPC API version
	 * "rpc-version-minimum"            | number     | the minimum RPC API version supported
	 * "script-torrent-done-filename"   | string     | filename of the script to run
	 * "script-torrent-done-enabled"    | boolean    | whether or not to call the "done" script
	 * "seedRatioLimit"                 | double     | the default seed ratio for torrents to use
	 * "seedRatioLimited"               | boolean    | true if seedRatioLimit is honored by default
	 * "seed-queue-size"                | number     | max number of torrents to uploaded at once (see seed-queue-enabled)
	 * "seed-queue-enabled"             | boolean    | if true, limit how many torrents can be uploaded at once
	 * "speed-limit-down"               | number     | max global download speed (KBps)
	 * "speed-limit-down-enabled"       | boolean    | true means enabled
	 * "speed-limit-up"                 | number     | max global upload speed (KBps)
	 * "speed-limit-up-enabled"         | boolean    | true means enabled
	 * "start-added-torrents"           | boolean    | true means added torrents will be started right away
	 * "trash-original-torrent-files"   | boolean    | true means the .torrent file of added torrents will be deleted
	 * "units"                          | object     | see below
	 * "utp-enabled"                    | boolean    | true means allow utp
	 * "version"                        | string     | long version string "$version ($revision)"
	 * ---------------------------------+------------+-----------------------------+
	 * units                            | object containing:                       |
	 * 	                            	+--------------+--------+------------------+
	 * 	                            	| speed-units  | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
	 * 	                            	| speed-bytes  | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
	 * 									| size-units   | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
	 * 									| size-bytes   | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
	 * 	                            	| memory-units | array  | 4 strings: KB/s, MB/s, GB/s, TB/s
	 * 									| memory-bytes | number | number of bytes in a KB (1000 for kB; 1024 for KiB)
	 * 	                            	+--------------+--------+------------------+
	 * @param null $needed
	 * @return bool|mixed
	 */
	public function get_session_vars ($needed = NULL)
	{
		$result = $this->_request ('session-get', []);
		return $this->_result ($result, 'arguments', $needed);
	}

	/**
	 * Set session params
	 *
	 * see argument of previous method, except:
	 * "blocklist-size", "config-dir", "rpc-version", "rpc-version-minimum", and "version"
	 *
	 * @param array $args
	 * @return bool|mixed
	 */
	public function set_session_vars ($args = [])
	{
		$result = $this->_request ('session-set', $args);
		return $this->_result ($result, TRUE);
	}

	/**
	 * Enable/disable altspeed
	 * @param bool $enabled
	 * @return bool|mixed
	 */
	public function altspeed ($enabled = TRUE)
	{
		return $this->set_session_vars (['alt-speed-enabled'=>$enabled]);
	}

	/**
	 * @param null $needed
	 * @return bool|mixed
	 */
	public function get_stats ($needed = NULL)
	{
		$result = $this->_request ('session-stats', []);
		return $this->_result ($result, 'arguments', $needed);
	}

	/**
	 * Transmission RPC response wrapper
	 * Returns specified path of array or throws error if detected
	 * @param $data
	 * @param bool $path
	 * @param array $needed
	 * @return bool|mixed
	 * @throws Exception
	 */
	private function _result ($data, $path = FALSE, $needed = [])
	{
		if (empty($data))
			return FALSE;

		$result = Arr::get($data, 'result');

		if ($result != 'success') {
			throw new Exception ($result);
		}
		elseif ($path) {

			if ($path === TRUE) {
				return TRUE;
			} else {
				if (is_scalar($needed) AND ! empty ($needed)) {
					$path .= '.' . $needed;
				}
				$data = Arr::path ($data, $path);
			}

			if ( is_array ($needed) AND ! empty ($needed)) {
				$data = array_intersect_key($data, array_flip($needed));
			}
		}

		if ($data AND ! empty ($this->_populate)) {
			$this->_populate_data ($data);
		}

		return $data;
	}

	/**
	 * Get readable torrent status
	 * @param $status
	 * @return string
	 */
	private function _get_torrent_status ($status)
	{
		switch ($status) {
			case TransmissionRPC::TR_STATUS_STOPPED:
				return 'Stopped';

			case TransmissionRPC::TR_STATUS_CHECK_WAIT:
				return 'Queued to check';

			case TransmissionRPC::TR_STATUS_CHECK:
				return 'Checking files';

			case TransmissionRPC::TR_STATUS_DOWNLOAD_WAIT:
				return 'Queued to download';

			case TransmissionRPC::TR_STATUS_DOWNLOAD:
				return 'Downloading';

			case TransmissionRPC::TR_STATUS_SEED_WAIT:
				return 'Queued to seed';

			case TransmissionRPC::TR_STATUS_SEED:
				return 'Seeding';

			default:
				return 'Unknown';
		}
	}

	/**
	 * Get readable filesize
	 * @param $bytes
	 * @param int $decimals
	 * @return string
	 */
	private function _human_filesize ($bytes, $decimals = 2) {
	    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	    $factor = floor((strlen($bytes) - 1) / 3);
	    return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)) . ' ' . @$size[$factor];
	}

	/**
	 * Date formatter
	 * @param $ts
	 * @return string
	 */
	private function _get_formatted_date ($ts)
	{
		$dt = new \DateTime (NULL, new \DateTimeZone ($this->_timezone));
		$dt->setTimestamp ($ts);
		return $dt->format($this->_datefmt);
	}

	/**
	 * Populate data
	 * @param $data
	 */
	private function _populate_data (&$data) {
		if (is_array ($data) AND ! empty ($data)) {
			foreach ($data as &$items) {
				if (is_array($items) AND ! empty ($items)) {
					foreach ($items as $key=>&$value) {
						if (is_array($value)) {
							$this->_populate_data($value);
						}
						switch ($key) {
							case 'status':
								if ($this->_populate === TRUE OR in_array ('status', $this->_populate)) {
									$value = $this->_get_torrent_status($value);
								}
							break;

							case 'doneDate':
							case 'startDate':
							case 'activityDate':
							case 'addedDate':
								if ($this->_populate === TRUE OR in_array ('date', $this->_populate)) {
									$value = $value ? $this->_get_formatted_date($value) : NULL;
								}
							break;

							case 'totalSize':
							case 'haveValid':
							case 'length':
							case 'sizeWhenDone':
								if ($this->_populate === TRUE OR in_array ('size', $this->_populate)) {
									$value = $this->_human_filesize($value);
								}
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Send request to RPC
	 * @param null $method
	 * @param array $args
	 * @return mixed
	 * @throws Exception
	 */
	private function _request ($method = NULL, $args = [])
	{
		if ( ! is_scalar($method)) {
	    	throw new Exception ('Method name has no scalar value', Exception::E_INVALIDARGS);
	    }

	    if ( ! is_array($args)) {
	    	throw new Exception ('Params must be given as array', Exception::E_INVALIDARGS);
	    }

	    switch ($method)
		{
			case 'get_session_id':
				$params = [];
				$method = 'head';
			break;

			default:

				if (empty($this->_session_id)) {
					$this->_request('get_session_id', []);
				}

				if (isset ($args['ids'])) {
					if ( ! is_array($args['ids'])) {
						$args['ids'] = (array) $args['ids'];
					}
				}

				$this->_sanitize_data ($args);

				$params = array(
					'method'    => $method,
					'arguments' => $args
				);

				$method = 'post';
		}

		$request = CurlClient::$method($this->_endpoint, $params)
						->user_agent(TransmissionRPC::UA)
						->accept('gzip', 'json');

	    if ($method === 'post') {
	    	$request->json()
					->header('X-Transmission-Session-Id', $this->_session_id);
		}

		if ($this->_username AND $this->_password) {
			$request->auth($this->_username, $this->_password, 'basic');
		}

		$response = $request->send();
		$status = $response->get_status();

		switch ($status) {
			case 200 :
				return $response->get_body();
			break;

			case 409:
				if ($method === 'head') {
					$this->_session_id = $response->get_headers()->offsetGet('x-transmission-session-id');
					//$this->_session_id = Arr::get ($headers, 'X-Transmission-Session-Id');
					if (empty ($this->_session_id)) {
						throw new Exception ('Unable to retrieve X-Transmission-Session-Id', Exception::E_SESSIONID);
					}
				} else {
					throw new Exception ('Invalid X-Transmission-Session-Id', Exception::E_SESSIONID);
				}
			break;

			case 401:
				throw new Exception ('Autentification needed. Invalid username/password', Exception::E_AUTHENTICATION);
			break;

			default:
				throw new Exception ('Unexpected response from Transmission RPC', $status);
		}
	}

	/**
	 * Sanitize data
	 * @param $data
	 * @return null
	 */
	private function _sanitize_data (&$data)
	{
		if ( ! is_array ($data) OR empty ($data)) {
			return NULL;
		}

		foreach ($data as $key=>&$value) {

			if (is_object($value)) {
				$value = $value->toArray();
			}

			if (is_array($value)) {
				$this->_sanitize_data ($value);
			}

			if (empty ($value) AND $value !== 0 AND $value !== FALSE) {
				unset($data[$key]);
				continue;
			}

			if (is_numeric($value)) {
				$value += 0;
			}

			if (is_bool($value)) {
				$value = intval ($value);
			}

			if (is_string ($value)) {
				if (mb_detect_encoding($value, 'auto') !== 'UTF-8') {
				  $value = mb_convert_encoding($value, 'UTF-8');
				}
			}
		}
	}
}
