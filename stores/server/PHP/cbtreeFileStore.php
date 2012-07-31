<?php
	/****************************************************************************************
	*	Copyright (c) 2012, Peter Jekel
	*	All rights reserved.
	*
	*		The cbtree FileStore Server Side Application (cbtreeFileStore.php) is released under
	*		to following license:
	*
	*	    BSD 2-Clause		(http://thejekels.com/cbtree/LICENSE)
	*
	*	@author		Peter Jekel
	*
	*	@date			07/01/2012
	*
	*	@version	1.0
	*
	*	History:
	*
	*		1.0			07/01/12	Initial release
	*
	*****************************************************************************************
	*
	*		Description:
	*
	*			This file contains the server side application required  to enable the dojo
	*			cbtree FileStore and is part of the github project 'cbtree'. Your server MUST
	*			provide support for PHP applications in order for it to work properly.
	*			Alternatively, an ANSI-C CGI application is also available. See the notes on
	*			performance below.
	*
	*			The cbtree FileStore.php application is invoked by means of a HTTP GET or DELETE
	*			request, the basic ABNF format of a request looks like:
	*
	*				HTTP-request	::= uri ('?' query-string)?
	*				query-string  ::= (qs-param ('&' qs-param)*)?
	*				qs-param		  ::= authToken | basePath | path | query | queryOptions | options | 
	*								 				  start | count | sort
	*				authToken			::= 'authToken' '=' json-object
	*				basePath		  ::= 'basePath' '=' path-rfc3986
	*				path				  ::= 'path' '=' path-rfc3986
	*				query			  	::= 'query' '=' json-object
	*				query-options ::= 'queryOptions' '=' json-object
	*				options			  ::= 'options' '=' json-array
	*				start			  	::= 'start' '=' number
	*				count				  ::= 'count' '=' number
	*				sort 					::= 'sort' '=' json-array
	*
	*			Please refer to http://json.org for the correct JSON encoding of the
	*			parameters.
	*
	*		NOTE:		Configuration of your server for either PHP or CGI support is beyond
	*						the scope of this document.
	*
	****************************************************************************************
	*
	*		QUERY-STRING Parameters:
	* 
	*			authToken:
	*
	*				The authToken parameter is a JSON object. There are no restrictions with
	*				regards to the content of the object. (currently not used).
	*
	*			basePath:
	*
	*				The basePath parameter is a URI reference (rfc 3986) relative to the server's
	*				document root used to compose the root directory as follows:
	*
	*					root-dir ::= document_root '/' basePath?
	*
	*			path:
	*
	*				The path parameter is used to specify a specific location relative to the
	*				above mentioned root_dir. Therfore, the full search path is:
	*
	*					full-path = root_dir '/' path?
	*
	*			query:
	*
	*				The query parameter is a JSON object with a set of JSON 'property:value'
	*				pairs. If specified, only files that match the query criteria are returned.
	*				If the property value is a string it is treated as a pattern string.
	*
	*				Example:	query={"name":"*.js"}
	*
	*			queryOptions:
	*
	*				The queryOptions parameter specifies a set of JSON 'property:value' pairs
	*				used during the file search. Currently two properties are supported: "deep"
	*				and "ignoreCase". Property deep indicates if a recursive search is required
	*				whereas ignoreCase indicates if values are to be compared case insensitive/
	*
	*				Example:	queryOptions={"deep":true, "ignorecase":true}
	*
	*			options:
	*
	*				The options parameter is a JSON array of strings. Each string specifying a
	*				search options to be enabled. Currently the following options are supported:
	*				"dirsOnly" and "showHiddenFiles".
	*
	*				Example:	options=["dirsOnly", "showHiddenFiles"]
	*
	*			start:
	*
	*				The start parameter identifies the first entry in the files list to be returned
	*				to the caller. The default is 0. Note: start is a zero-based index.
	*
	*			count:
	*
	*				Parameter count specifies the maximum number of files to be returned. If zero
	*				(default) all files, relative to the start position, are returned.
	*
	*			sort:
	*
	*				Parameter sort is a JSON array of JSON objects. If specified the files list
	*				is sorted in the order the JSON objects are arranged in the array.
	*				The properties allowed are: "attribute", "descending" and "ignoreCase". Each
	*				sort field object MUST have the "attribute" property defined.
	*
	*				Example:	sort=[{"attribute":"directory", "descending":true},{"attribute":"name",
	*												 "ignoreCase":true}]
	*
	*				The example sort will return the file list with the directories first and
	*				all names in ascending order. (A typical UI file tree).
	*
	****************************************************************************************
	*
	*		ENVIRONMENT VARIABLE:
	*
	*			CBTREE_BASEPATH
	*
	*				The basePath is a URI reference (rfc 3986) relative to the server's
	*				document root used to compose the root directory.  If this variable
	*				is set it overwrites the basePath parameter in any query string and
	*				therefore becomes the server wide basepath.
	*
	*					CBTREE_BASEPATH /myServer/wide/path
	*
	*			CBTREE_METHODS
	*
	*				A comma separated list of HTTP methods to be supported by the Server
	*				Side Application. By default only HTTP GET is supported. Example:
	*
	*					CBTREE_METHODS GET,DELETE
	*
	*		Notes:
	*
	*			-	Some HTTP servers require  special configuration to make environment
	*				variables available to  script or CGI application.  For example, the
	*				Apache HTTP servers requires you to either use the SetEnv or PassEnv 
	*				directive. To make the environment variable CBTREE_METHODS available
	*				add the following to your httpd.conf file:
	*
	*					SetEnv CBTREE_METHODS GET,DELETE
	*											or
	*					PassEnv CBTREE_METHODS
	*
	*				(See http://httpd.apache.org/docs/2.2/mod/mod_env.html for details).
	*
	****************************************************************************************
	*
	*		PERFORMACE:
	*
	*				If you plan on using this cbtreeFileStore  on large file systems with, for
	*				example, a  checkbox tree that requires a strict parent-child relationship
	*				it is highly recommended to use the ANSI-C CGI implementation instead, that
	*				is, assuming your server is configured to provide CGI support. 
	*				PHP is an interpreter and relatively slow compared to native compiled CGI
	*				applications. A Microsoft Windows version of the ANSI-C CGI application is
	*				available.
	*
	*				To configure an Apache HTTP server for CGI support please refer to:
	*
	*						http://httpd.apache.org/docs/2.2/howto/cgi.html
	*
	*		NOTE:	When using the ANSI-C CGI implementation no PHP support is required.
	*
	****************************************************************************************
	*
	*		SECURITY:
	*
	*				Some  basic security issues are addressed  by this implementation.  For example,
	*				only HTTP methods allowed are served. Malformed QUERY-STRING parameters are NOT
	*				skipped and  ignored, instead they will result  in a 'Bad Request' response  to
	*				the server/client. Requests to access files above the server's document root are
	*				rejected returning the HTTP forbidden response (403).
	*
	*		AUTHENTICATION:
	*
	*				This application does NOT authenticate the calling party however, it does test
	*				for, and retreives, a 'authToken' paramter if present.
	*
	*		NOTE:	This implementation will not list any files starting with a dot like .htaccess
	*					unless explicitly requested. However it will NOT process .htaccess files.
	*					Therefore, it is the user's responsibility not to include any private or other
	*					hidden files in the directory tree accessible to this application.
	*
	***************************************************************************************
	*
	*		RESPONSES:
	*
	*				Assuming a valid HTTP GET or DELETE request was received the response to
	*				the client complies with the following ABNF notation:
	*
	*					response	  	::= '[' (totals ',')? (status ',')? (identifier ',')? (label ',')? file-list ']'
	*					totals 				::= '"total"' ':' number
	*					status				::= '"status"' ':' status-code
	*					status-code		::=	'200' | '204'
	*					identifier		::= '"identifier"' ':' quoted-string
	*					label		  		::= '"label"' ':' quoted-string
	*					file-list			::= '"items"' ':' '[' file-info* ']'
	*					file-info			::= '{' name ',' path ',' size ',' modified ',' directory 
	*														(',' children ',' expanded)? '}'
	*					name					::= '"name"' ':' json-string
	*					path					::= '"path"' ':' json-string
	*					size					::= '"size"' ':' number
	*					modified			::= '"modified"' ':' number
	*					directory			::= '"directory"' ':' ('true' | 'false')
	*					children			::= '[' file-info* ']'
	*					expanded			::= '"_EX"' ':' ('true' | 'false')
	*					quoted-string ::= '"' CHAR* '"'
	*					number				::= DIGIT+
	*					DIGIT					::= '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9'
	*
	*		Notes:
	*
	*				-	The file-info path is returned as a so-called rootless path, that is,
	*					without a leading dot and forward slash. (see rfc-3986 for details).
	*				-	The expanded property indicates if a deep search was performed on a 
	*					directory. Therefore, if expanded is true and children is empty we
	*					are dealing with an empty directory and not a directory that hasn't
	*					been searched/expanded yet. The expanded property is typically used
	*					when lazy loading the file store.
	*
	***************************************************************************************/

	// Define the possible HTTP result codes returned by this application.
	define( "HTTP_V_OK", 								200);
	define( "HTTP_V_NO_CONTENT",					204);
	define( "HTTP_V_BAD_REQUEST",				400);
	define( "HTTP_V_UNAUTHORIZED",				401);
	define( "HTTP_V_FORBIDDEN",					403);
	define( "HTTP_V_NOT_FOUND",					404);
	define( "HTTP_V_METHOD_NOT_ALLOWED",	405);
	define( "HTTP_V_CONFLICT",						409);
	define( "HTTP_V_GONE",								410);
	define( "HTTP_V_SERVER_ERROR",				500);

	define( "STORE_C_IDENTIFIER", "path" );
	define( "STORE_C_LABEL", "name" );
	
	$docRoot = $_SERVER["DOCUMENT_ROOT"];

	$method = null;
	$files	= null;
	$total	= 0;
	$status	= 0;

	$method = $_SERVER["REQUEST_METHOD"];

	// Check the HTTP method first.
	if (!cgiMethodAllowed($method)) {
		cgiResponse( HTTP_V_METHOD_NOT_ALLOWED, "Method Not Allowed", NULL);
		error_log( "Invalid or unsupported method: [".$method."]");
		return;
	}

	// Validate the HTTP QUERY-STRING parameters
	$args	= getArguments($method, $status);
	if ($args == null ) {
		cgiResponse( HTTP_V_BAD_REQUEST, "Bad Request", "Malformed query arguments." );
		return;
	}

	if ($args->authToken) {
		// Your authentication may go here....
	}

	$rootDir  = str_replace( "\\","/", realPath( $docRoot . "/" . $args->basePath ));
	$fullPath = str_replace( "\\","/", realPath( $rootDir . "/" . $args->path ));

	if ($rootDir && $fullPath) {
		// Make sure the caller isn't backtracking by specifying paths like '../../../'
		if ( strncmp($rootDir, $docRoot, strlen($docRoot)) || strncmp($fullPath, $rootDir, strlen($rootDir)) ) {
			cgiResponse( HTTP_V_FORBIDDEN, "Forbidden", "We're not going there..." );
			return;
		}

		switch($method) {
			case "DELETE":
				$files = getFile( $fullPath, $rootDir, $args, $status );
				if ($files) {
					$files = removeFile( $files[0], $rootDir, $args, $status );
					// Compile the final result
					$result							= new stdClass();
					$result->total			= count($files);
					$result->status			= $status;
					$result->items			= $files;

					header("Content-Type: text/json");
					print( json_encode($result) );
				} else {
					cgiResponse( $status, "Not Found", null );
				}
				break;

			case "GET":
				if (!is_string($args->path)) {
					if ($args->query) {
						$files = getMatch( $fullPath, $rootDir, $args, $status );
					} else {
						$files = getDirectory( $fullPath, $rootDir, $args, $status );
					}
				} else {
					$files = getFile( $fullPath, $rootDir, $args, $status );
				}
				if( ($total = count($files)) ) {
					// sort, slice and dice
					if ($args->sortList != null) {
						usort($files, array($args->sortList, "fileCompare"));
					}
					if( $args->start || $args->count ) {
						$files = array_slice($files, $args-start, $args->count);
						$total = count($files);
					}
				}
				// Compile the final result
				$result							= new stdClass();
				$result->identifier = STORE_C_IDENTIFIER;
				$result->label			= STORE_C_LABEL;
				$result->total			= $total;
				$result->status			= $total ? HTTP_V_OK : HTTP_V_NO_CONTENT;
				$result->items			= $files;

				header("Content-Type: text/json");
				print( json_encode($result) );
				break;

			case "POST":
				switch($args->attribute) {
					case STORE_C_IDENTIFIER:
					case STORE_C_LABEL:
						$files = renameFile( $fullPath, $rootDir, $args, $status );
						// Compile the final result
						if ($status == HTTP_V_OK) {
							$result							= new stdClass();
							$result->total			= count($files);
							$result->status			= $status;
							$result->items			= $files;

							header("Content-Type: text/json");
							print( json_encode($result) );
						} else {
							cgiResponse( $status, "system error.", "Failed to rename file." );
						}
						break;
					default:
						cgiResponse( HTTP_V_BAD_REQUEST, "Bad Request", "Attribute not allowed." );
						break;
				}
				break;
		}
	}	else {
		cgiResponse( HTTP_V_NOT_FOUND, "Not Found", "Invalid path and/or basePath." );
	}

	/**
	*		cgiMethodAllowed
	*
	*			Returns true if the HTTP method is allowed, that is, supported by this
	*			application.
	*
	*		@param	method				Method name string.
	*
	*		@return		true or false
	**/
	function cgiMethodAllowed( /*string*/ $method ) {
		$allowed = "GET," . getenv("CBTREE_METHODS");
		$methods = explode(",", $allowed);
		$count   = count($methods);
		
		for ($i = 0;$i<$count; $i++) {
			if ($method == trim($methods[$i])) {
				return true;
			}
		}
		return false;
	}

	/**
	*		cgiResponse
	*
	*			Sends a CGI response back to the caller.
	*
	*	@param	status					HTTP result code
	*	@param	statText				HTTP reason phrase.
	*	@param	infoText				Optional text returned to the caller.
	**/
	function cgiResponse( $status, $statText, $infoText = null) {
		header("Content-Type: text/html");
		header("Status: " . $status . $statText );
		if( $infoText ) {
			print( $infoText );
		}
	}

	/**
	*		fileFilter
	*
	*			Returns true if a file is to be exlcuded (filtered) based on the HTTP query
	*			string parameters such as 'dirsOnly' or 'showHiddenFiles', otherwise false.
	*			The current and parent directory entries are excluded by default.
	*
	*	@param	fileInfo
	*	@param	args
	*
	*	@return	true or false
	**/
	function fileFilter( /*object*/$fileInfo, /*object*/$args ) {
		if ( (!$args->showHiddenFiles && $fileInfo->name[0] == ".") ||
				 ($args->dirsOnly && !$fileInfo->directory) ||
				 ($fileInfo->name == ".." || $fileInfo->name == ".") ) {
					return true;
		}
		return false;
	}

	/**
	*		fileMatchQuery
	*
	*			Returns true if a file matches all query arguments otherwise false.
	*
	*	@param	fileInfo				File info object.
	*	@param	query						Query object containing an array of query arguments.
	*
	*	@return		True if the file matches ALL query arguments otherwise false.
	**/
	function fileMatchQuery( /*object*/$fileInfo, /*object*/$query ) {
		$match = true;
		for ($i = 0; $i < $query->count && $match == true; $i++) {
			$queryArgm = $query->argm[$i];
			$property	 = $queryArgm->property;

			$propVal  = property_exists($fileInfo, $property) ? $fileInfo->$property : null;
			$queryVal = $queryArgm->propVal;

			if ($queryArgm->pattern) {
				$match = (bool)preg_match($queryArgm->pattern, $propVal);
			} else {
				if(	$propVal != $queryVal ) {
					$match = false;
				}
			}
		}
		return $match;
	}

	/**
	*		fileToStruct
	*
	*			Create a FILE_INFO object
	*
	*	@param	fullPath				Full path string (directory path)
	*	@param	rootDir					Root directory
	*	@param	filename				Filename
	*
	*	@return		FILE_INFO object.
	*
	*	@TODO		Remove directory property on normal files without impacting the
	*					preformance too much making the result returned identical to the
	*					ANSI-C CGI implementation. 
	*					(PHP generates an error when referencing non-existing properties).
	**/
	function fileToStruct( /*string*/$fullPath, /*string*/$rootDir, /*string*/$filename, /*object*/$args ) {
		$uriPath  = $fullPath . "/" . $filename;
		$atts 	  = stat( $uriPath );
		
		$relPath  = substr( $uriPath, (strlen($rootDir)+1), strlen($uriPath) );
		$relPath  = str_replace( "\\", "/", $relPath );

		$fileInfo							= new stdClass();
		$fileInfo->name 			= $filename;
		$fileInfo->path 			= $relPath;
		$fileInfo->size 			= filesize($uriPath);
		$fileInfo->modified 	= $atts[9];
		$fileInfo->directory	= is_dir($uriPath);

		if ($fileInfo->directory) {
			$fileInfo->children = array();
			$fileInfo->_EX      = false;
		}
		return $fileInfo;
	}

	/**
	*		getArguments
	*
	*			Returns an ARGS object with all HTTP QUERY-STRING parameters extracted and
	*			decoded. See the description on top for the ABNF notation of the parameter.
	*
	*	@note		All QUERY-STRING parameters are optional, if however a parameter is
	*					specified it MUST comply with the formentioned ABNF format. 
	*					For security, invalid formatted parameters are not skipped or ignored,
	*					instead they will result in a HTTP Bad Request status (400).
	*
	*	@param	status					Receives the final result code. (200 or 400)
	*
	*	@return		On success an 'args' object otherwise NULL
	**/
	function getArguments( $method, /*integer*/&$status ) {

		$status	= HTTP_V_BAD_REQUEST;		// Lets assume its a malformed query string
		$_ARGS  = null;
		
		$args										= new stdClass();
		$args->authToken				= null;
		$args->basePath					= "";
		$args->deep 						= false;
		$args->dirsOnly 				= false;
		$args->path 						= null;
		$args->showHiddenFiles	= false;
		$args->sortList 			  = null;
				
		switch ($method) {
			case "DELETE":
				$_ARGS = $_GET;
				if (!array_key_exists("path", $_ARGS)) {
					return null;
				}
				break;

			case "GET":
				$_ARGS = $_GET;

				$args->count						= 0;
				$args->files 					  = array();
				$args->ignoreCase 			= false;
				$args->loadAll					= false;
				$args->query 						= null;
				$args->rootDir					= "";
				$args->start						= 0;

				// Get the 'options' and 'queryOptions' first before processing any other parameters.
				if (array_key_exists("options", $_ARGS)) {
					$options = str_replace("\\\"", "\"", $_ARGS['options']);
					$options = json_decode($options);
					if (is_array($options)) {
						if (array_search("dirsOnly", $options) > -1) {
							$args->dirsOnly = true;
						}
						if (array_search("showHiddenFiles", $options) > -1) {
							$args->showHiddenFiles = true;
						}
					}	
					else	// options is not an array.
					{
						return null;
					}
				}
				if (array_key_exists("queryOptions", $_ARGS)) {
					$queryOptions = str_replace("\\\"", "\"", $_ARGS['queryOptions']);
					$queryOptions = json_decode($queryOptions);
					if (is_object($queryOptions)) {
						if (property_exists($queryOptions, "deep")) {
							$args->deep = $queryOptions->deep;
						}
						// @note	Options 'deep' and 'loadAll' have the same meaning on the server
						//				side however, they are handled different by the cbtreeFileStore
						//				on the client side. For example, if the store is used with a tree
						//				that requires a strict parent-child relationship ALL files MUST
						//				be loaded overwriting 'lazy loading'. (see notes on performance
						//				above).

						if (property_exists($queryOptions, "loadAll")) {
							$args->loadAll = $queryOptions->loadAll;
							if( $args->loadAll ) {
								$args->deep = true;
							}
						}
						if (property_exists($queryOptions, "ignoreCase")) {
							$args->ignoreCase = $queryOptions->ignoreCase;
						}
					}
					else	// queryOptions is not an object.
					{
						return null;
					}
				}

				if (array_key_exists("query", $_ARGS)) {
					// decode query into an associative array.
					$query = str_replace("\\\"", "\"", $_ARGS['query']);
					$query = json_decode($query, true);

					if (is_array($query)) {
						$args->query = getQueryArgs($query, $args->ignoreCase);
					}
					else	// query is not an array.
					{
						return null;
					}
				}
				if (array_key_exists("sort", $_ARGS)) {
					$sortFields = str_replace("\\\"", "\"", $_ARGS['sort']);
					$sortFields = json_decode($sortFields);
					if (is_array($sortFields)) {
						$args->sortList = getSortArgs($sortFields);
					}
					else	// sort is not an array.
					{
						return null;
					}
				}
				if (array_key_exists("start", $_ARGS)) {
					$start = $_ARGS['start'];
					if (is_numeric($start)) {
						$args->start = $start;
					}
				}
				if (array_key_exists("count", $_ARGS)) {
					$count = $_ARGS['count'];
					if (is_numeric($count)) {
						$args->count = $count;
					}
				}
				break;

			case "POST":
				$_ARGS = $_POST;
				
				$args->attribute = null;
				$args->newValue  = null;
				
				if( !array_key_exists("attribute", $_ARGS) || 
						!array_key_exists("newValue", $_ARGS) || 
						!array_key_exists("oldValue", $_ARGS) || 
						!array_key_exists("path", $_ARGS)) {
					return null;
				}
				if (is_string($_ARGS["attribute"])) {
					$args->attribute = $_ARGS["attribute"];
				} else {
					return null;
				}
				if (is_string($_ARGS['newValue'])) {
					$args->newValue = $_ARGS["newValue"];
				} else {
					return null;
				}
				if (is_string($_ARGS['oldValue'])) {
					$args->oldValue = $_ARGS["oldValue"];
				} else {
					return null;
				}
				break;
		} /* end switch($method) */

		// Get authentication token. There are no restrictions with regards to the content
		// of the object.
		if (array_key_exists("authToken", $_ARGS)) {
			$authToken = str_replace("\\\"", "\"", $_ARGS['authToken']);
			$authToken = json_decode($authToken);
			if ($authToken) { 
				$args->authToken = $authToken;
			}
		}
		// Check for a basePath
		$args->basePath = getenv("CBTREE_BASEPATH");
		if (!$args->basePath) {
			if (array_key_exists("basePath", $_ARGS)) {
				$args->basePath = $_ARGS['basePath'];
			}
		}
		if ($args->basePath && !is_string($args->basePath)) {
			return null;
		}

		//	Check if a specific path is specified.
		if (array_key_exists("path", $_ARGS)) {
			if (is_string($_ARGS['path'])) {
				$args->path = $_ARGS['path'];
			} else {
				return null;
			}
		}
		$status = HTTP_V_OK;		// Return success
		return $args;
	}

	/**
	*		getDirectory
	*
	*			Returns the content of a directory as an array of FILE_INFO objects.
	*
	*	@param	fullPath				Full path string (directory path)
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 204 or 404).
	*
	*	@return		An array of FILE_INFO objects or NULL in case no match was found.
	**/
	function getDirectory( /*string*/$fullPath, /*string*/$rootDir, /*object*/$args, /*number*/&$status ) {
		if( ($dirHandle = opendir($fullPath)) ) {
			$files = array();
			$stat	 = 0;
			while($file = readdir($dirHandle)) {
				$fileInfo = fileToStruct( $fullPath, $rootDir, $file, $args );
				if (!fileFilter( $fileInfo, $args )) {
					if ($fileInfo->directory && $args->deep) {
						$path = $rootDir . "/" . $fileInfo->path;
						$children = getDirectory( $path, $rootDir, $args, $stat );
						if ($children && $args->sortList != null) {
							usort($children, array($args->sortList, "fileCompare"));
						}
						$fileInfo->children = $children;
						$fileInfo->_EX      = true;
					}
					$files[] = $fileInfo;
				}
			}
			$status = $files ? HTTP_V_OK : HTTP_V_NO_CONTENT;
			closedir($dirHandle);
			return $files;
		}
		$status = HTTP_V_NOT_FOUND;
		return null;
	}
	
	/**
	*		getFile
	*
	*			Returns the information for the file specified by parameter fullPath.
	*			If the designated file is a directory the directory content is returned
	*			as the children of the file.
	*
	*	@param	fullPath				Full path string (file path)
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 204 or 404).
	*
	*	@return		An array of 1 FILE_INFO object or NULL in case no match was found.
	**/
	function getFile( /*string*/$fullPath, /*string*/$rootDir, /*object*/$args, /*number*/&$status ) {
		if( file_exists( $fullPath ) ) {
			$files 		= array();
			$stat			= 0;
			$segment  = strrchr( $fullPath, "/" );
			$filename = substr( $segment, 1 );
			$path     = substr( $fullPath, 0, (strlen($fullPath) - strlen($segment)) );
			$fileInfo = fileToStruct( $path, $rootDir, $filename, $args );

			if (!fileFilter( $fileInfo, $args )) {
				if ($fileInfo->directory) {
					$children = getDirectory( $fullPath, $rootDir, $args, $stat );
					if ($children && $args->sortList != null) {
						usort($children, array($args->sortList, "fileCompare"));
					}
					$fileInfo->children = $children;
					$fileInfo->_EX      = true;
				}
				$files[] = $fileInfo;
			}
			$status = $files ? HTTP_V_OK : HTTP_V_NO_CONTENT;
			return $files;
		}
		$status = HTTP_V_NOT_FOUND;
		return null;
	}

	/**
	*		getMatch
	*
	*			Returns an array of FILE_INFO objects that match ALL query arguments. Whenever
	*			a deep (recursive) search is requested all sub-directories are searched and
	*			any matching children are merged with the top-level matches. Therefore, the
	*			list returned does NOT have a tree structure, instead it is a flat file list.
	*
	*	@param	fullPath				Full path string (directory path)
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 204 or 404).
	*
	*	@return		An array of FILE_INFO objects or NULL in case no match was found.
	**/
	function getMatch( /*string*/$fullPath, /*string*/$rootDir, /*object*/$args, /*number*/&$status ) {
		if( ($dirHandle = opendir($fullPath)) ) {
			$files = array();
			$stat	 = 0;
			while($file = readdir($dirHandle)) {
				$fileInfo = fileToStruct( $fullPath, $rootDir, $file, $args );
				if (!fileFilter( $fileInfo, $args )) {
					if (fileMatchQuery( $fileInfo, $args->query )) {
						$files[] = $fileInfo;
					}
					if ($fileInfo->directory && $args->deep) {
						$path = $rootDir . "/" . $fileInfo->path;
						$subFiles = getMatch( $path, $rootDir, $args, $stat );
						if( $subFiles ) {
							$files = array_merge( $files, $subFiles );
						}
					}
				}
			}
			$status = $files ? HTTP_V_OK : HTTP_V_NO_CONTENT;
			closedir($dirHandle);
			return $files;
		}
		$status = HTTP_V_NOT_FOUND;
		return null;
	}
	
	/**
	*		getQueryArgs
	*
	*			Returns a query object containing an array of query arguments.   Parameter query
	*			represents the JSON decoded 'query' argument of the HTTP QUERY-STRING. If a query
	*			parameter value is a pattern string it is converted to a Perl Compatible Regular
	*			Expression (PCRE)
	*
	*	@param	query						JSON decoded array of query arguments.
	*	@param	ignoreCase			Indicates if regular expression are case insensitive.
	*
	*	@return		query object or null in case the query array was empty.
	**/
	function getQueryArgs( /*array*/$query, /*object*/$ignoreCase ) {
		$keys  = array_keys($query);	
		if (($total = count($keys))) {
			$queryObj				 = new stdClass;
			$queryObj->count = 0;
			$queryObj->argm	 = array();
		
			for ($i = 0; $i < $total; $i++) {
				$queryArgm 						= new stdClass;
				$queryArgm->property	= $keys[$i];
				
				$propVal = $query[$queryArgm->property];
				if (is_string($propVal)) {
					$queryArgm->pattern = patternToRegExp($propVal, $ignoreCase);
					$queryArgm->propVal = null;
				} else {
					$queryArgm->propVal = $propVal;
					$queryArgm->pattern = null;
				}
				$queryObj->argm[] = $queryArgm;
				$queryObj->count++;
			}
			return $queryObj;
		}
		return null;			// Empty query object
	}

	/**
	*		getSortArgs
	*
	*			Returns a sort object/class containing an array of valid sort arguments (fields).
	*
	*	@param	sortFields			An array of sort field objects
	*
	*	@return		SortList object or null.
	**/
	function getSortArgs(/*array*/$sortFields) {
		if (($total = count($sortFields))) {
			$sortObj = new SortList;

			for ($i = 0; $i < $total; $i++) {
				$sortField = $sortFields[$i];
				// The sort field object must have the 'attribute' property.
				if (property_exists($sortField, "attribute")) {
					$sortArgm	= new stdClass;
					$sortArgm->property		= $sortField->attribute;
					$sortArgm->descending = property_exists($sortField, "descending") ? $sortField->descending : false;
					$sortArgm->ignoreCase = property_exists($sortField, "ignoreCase") ? $sortField->ignoreCase : false;

					$sortObj->addSortArgm($sortArgm);
				}
			}
			return ($sortObj->count ? $sortObj : null);
		}
		return null;
	}

	/**
	*		patterToRegExp
	*
	*			Convert a pattern string to a Perl Compatible Regular Expression (PCRE).
	*
	*	@param	pattern					String containing the pattern string
	*	@param	ignoreCase			Indicates if matching is case insensitive.
	*
	*	@return		Regular expression string.
	**/
	function patternToRegExp(/*String*/$pattern, /*Boolean*/ $ignoreCase){
		$regExp = "";
		$char 	= "";
		$len		= strlen($pattern);

		for ($i = 0; $i < $len; $i++) {
			$char = $pattern[$i];
			switch ($char) {
				case '\\':
					$regExp = $regExp.$char;
					$i++;
					$regExp = $regExp.$pattern[$i];
					break;
				case '*':
					$regExp = $regExp.".*"; break;
				case '?':
					$regExp = $regExp."."; break;
				case '$':
				case '^':
				case '/':
				case '+':
				case '.':
				case '|':
				case '(':
				case ')':
				case '{':
				case '}':
				case '[':
				case ']':
					$regExp = $regExp."\\";
					/* NO BREAK HERE */
				default:
					$regExp = $regExp . $char;
					break;
			}
		}
		if ($ignoreCase) {
			$regExp = "(^" . $regExp . "$)i";
		} else {
			$regExp = "(^" . $regExp . "$)";
		}
		return $regExp;
	}

	/**
	/*	realURL
	**/
	function realURL( $path ) {
		$url = "";
		do {
			$p = $path;
			if (!strncmp( $path, "../", 3) || !strncmp($path,"./", 2)) {
				$path = substr($path, strpos($path,"/")+1 );
				continue;
			}
			if (!strncmp( $path, "/./", 3)) {
				$path = "/". substr($path, 3);
				continue;
			}
			if (!strcmp( $path, "/.")) {
				$path = "/";
				continue;
			}
			if (!strncmp( $path, "/../", 4)) {
				$path = "/". substr($path, 4);
				$pos = strrpos($url,"/");
				$url = substr($url, 0, $pos);
				continue;
			}
			if (!strcmp( $path, "/..")) {
				$path = "/";
				$pos = strrpos($url,"/");
				$url = substr($url, 0, $pos);
				continue;
			}
			if (!strcmp( $path, "..") || !strcmp($path,".")) {
				break;
			}
			if($path[0] == '/' ) {
				if ($path[1] != '/') {
					$pos  = strcspn( $path, "/", 1 );
				} else {
					$pos = 1;
				}
			} else {
				$pos  = strcspn( $path, "/" );
			}
			$segm = substr( $path, 0, $pos );
			$path = substr( $path, $pos );
			$url  = $url . $segm;
			
		} while( $path != $p );
		return str_replace( "//", "/", $url );
	}

	/**
	*		_removeDirectory
	*
	*			Delete a directory. The content of the directory is deleted after which
	*			the directory itself is delete.
	*
	*	@param	fileList				Array of deleted files.
	*	@param	filePath				Full path string
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 401 or 404).
	**/
	function _removeDirectory( &$fileList, $filePath, $rootDir, $args, &$status ) {
		$files = getFile( $filePath, $rootDir, $args, $status );
		if ($files) {
			$directory = $files[0];
			$childList = $directory->children;

			// Set permission on the directory first.
			chmod($filePath, 0777);

			foreach($childList as $childInfo) {
				_removeFile($fileList, $childInfo, $rootDir, $args, $status );
			}
			return @rmdir( $filePath );
		}
		$status = HTTP_V_NOT_FOUND;
		return false;
	}

	/**
	*		_removeFile
	*
	*			Delete a file or directory. If the file is a directory the content of the
	*			directory is deleted resurcive. Deleted file(s) are added to the list of
	*			deleted files 'fileList'
	*
	*	@param	fileList				Array of deleted files.
	*	@param	fileInfo				FILE_INFO struct
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 401 or 404).
	*
	*	@return		True on success otherwise false.
	**/
	function _removeFile( &$fileList, $fileInfo, $rootDir, $args, &$status ) {
		$filePath = $rootDir . "/" . $fileInfo->path;
		$result   = false;
		$status		= HTTP_V_OK;

		if ($fileInfo->directory) {
			$result = _removeDirectory( $fileList, $filePath, $rootDir, $args, $status );
		} else {
			chmod($filePath, 0666);
			$result = @unlink($filePath);
		}
		if ($result) {
			$fileList[] = $fileInfo;
			$result			= true;
		} else {
			$status = HTTP_V_UNAUTHORIZED;
		}
		return $result;
	}

	/**
	*		removeFile
	*
	*			Delete a file or directory. If the file is a directory the content of the
	*			directory is deleted resurcive.
	*
	*	@param	fileInfo				FILE_INFO struct
	*	@param	fullPath				Full path string (file path)
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 204 or 404).
	*
	*	@return		The list of delete files.
	**/
	function removeFile( $fileInfo, $rootDir, $args, &$status ) {
		$status = HTTP_V_NO_CONTENT;
		
		if ($fileInfo) {
			$fileList = array();

			$args->showHiddenFiles = true;
			$args->deep						 = false;

			$result = _removeFile( $fileList, $fileInfo, $rootDir, $args, $status );
			return $fileList;
		}
		return null;
	}

	/**
	*		renameFile
	*
	*			Rename a file
	*
	*	@param	fullPath				Full path string (file path)
	*	@param	rootDir					Root directory
	*	@param	args						HTTP QUERY-STRING arguments decoded.
	*	@param	status					Receives the final result (200, 204 or 404).
	*
	*	@return		An array of 1 FILE_INFO object or NULL in case no match was found.
	**/
	function renameFile( $fullPath, $rootDir, $args, &$status ) {
		$status = HTTP_V_OK;
		$newPath;
		
		if( file_exists( $fullPath ) ) {
			$fileList = array();
			$segment  = strrchr( $fullPath, "/" );
			$filename = substr( $segment, 1 );
			$path     = substr( $fullPath, 0, (strlen($fullPath) - strlen($segment)) );

			switch ($args->attribute) {
				case STORE_C_IDENTIFIER:
					$newPath = $rootDir."/".$args->newValue;
					break;
				case STORE_C_LABEL:
					$newPath = $path."/".$args->newValue;
					break;
				default:
					$status = HTTP_V_BAD_REQUEST;
					return null;
			}
			$newPath = realURL( $newPath );

			if (!strncmp($newPath, $rootDir, strlen($rootDir))) {
				if (!file_exists( $newPath )) {
					if (rename( $fullPath, $newPath )) {
						$segment  	= strrchr( $newPath, "/" );
						$filename 	= substr( $segment, 1 );
						$path     	= substr( $newPath, 0, (strlen($newPath) - strlen($segment)) );
						$fileList[] = fileToStruct( $path, $rootDir, $filename, $args );
					} else {
						$status = HTTP_V_NOT_FOUND;
					}
				} else {
					$status = HTTP_V_CONFLICT;
				}
			} else {
				$status = HTTP_V_FORBIDDEN;
			}
			return $fileList;			
		}
		return null;
	}
	
	/**
	*		SortList
	*
	*			The SortList class acts as a container of sort arguments. A sort argument is
	*			an object identifying the file property to operate on, the sort order, that
	*			is, ascending or descending and if the comparason is case insensitive.
	*			
	**/
	class SortList {
		var	$sortArgm	= array();
		var	$count 		= 0;

		/**
		*		addSortArgm
		*
		*			Add a sort argument to the list of sort arguments.
		*
		*	@param	sortArgm
		**/
		function addSortArgm(/*object*/$sortArgm) {
			$this->sortArgm[] = $sortArgm;
			$this->count++;
		}

		/**
		*		fileCompare
		*
		*			Compare file A and file B.   All sort arguments (fields) are tested until
		*			a comparison test returns a non-zero value or there are no more arguments
		*			left to test.
		*
		*	@param	fileA					File info object
		*	@param	fileB					File info object
		*
		*	@return		-1, 0 or 1
		**/
		function fileCompare(/*object*/$fileA, /*object*/$fileB) {

			$result = 0;

			for ($i = 0; $i < $this->count && !$result; $i++) {
				$sortArgm = $this->sortArgm[$i];
				$property = $sortArgm->property;

				$valA = (property_exists($fileA, $property) ? $fileA->$property : null);
				$valB = (property_exists($fileB, $property) ? $fileB->$property : null);

				if (is_string($valA) && is_string($valB)) {
					if ($sortArgm->ignoreCase) {
						$result = strcasecmp($valA, $valB);
					} else {
						$result = strcmp($valA, $valB);
					}
				} 
				else	// valA and/or valB is not a string
				{
					if($valA !== null || $valB !== null) {
						if($valB === null || $valA > $valB ) {
							$result = 1;
						} else if($valA === null || $valA < $valB ) {
							$result = -1;
						}
					}
				}
				if ($sortArgm->descending == true) {
					$result = $result * -1;
				}
			}
			// Normalize the result returned.
			return ($result ? ($result > 0 ? 1: -1): 0);
		}
	}

?>