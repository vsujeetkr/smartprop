<?php
    
    /**
     * This file is part of the PHP Video Toolkit v2 package.
     *
     * @author Oliver Lillie (aka buggedcom) <publicmail@buggedcom.co.uk>
     * @license Dual licensed under MIT and GPLv2
     * @copyright Copyright (c) 2008-2014 Oliver Lillie <http://www.buggedcom.co.uk>
     * @package PHPVideoToolkit V2
     * @version 2.1.7-beta
     * @uses ffmpeg http://ffmpeg.sourceforge.net/
     */
     
    namespace PHPVideoToolkit;

    /**
     * This class provides generic data parsing for the output from FFmpeg from specific
     * media files. Parts of the code borrow heavily from Jorrit Schippers version of 
     * PHPVideoToolkit v 0.1.9.
     *
     * @access public
     * @author Oliver Lillie
     * @author Jorrit Schippers
     * @package default
     */
    class MediaParser extends MediaParserAbstract
    {
        /**
         * Returns the information about a specific media file.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return array
         */
        public function getFileInformation($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_information';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key)))
            {
                return $data;
            }
            
//          check to see if the info has already been generated
            if($read_from_cache === true && isset($file_data[$file_path]) === true)
            {
                return $file_data[$file_path];
            }
            
//          get the file data
            $data = array(
                'from-cache'=> true,
                'read-at'   => time(),
                'path'      => $file_path,
                'type'      => $this->getFileType($file_path, $read_from_cache),
                'container' => $this->getFileContainerFormat($file_path, $read_from_cache),
                'duration'  => $this->getFileDuration($file_path, $read_from_cache),
                'bitrate'   => $this->getFileBitrate($file_path, $read_from_cache),
                'volume'    => $this->getFileVolumeComponent($file_path, $read_from_cache),
                'start'     => $this->getFileStart($file_path, $read_from_cache),
                'video'     => $this->getFileVideoComponent($file_path, $read_from_cache),
                'audio'     => $this->getFileAudioComponent($file_path, $read_from_cache),
                'metadata'  => $this->getFileGlobalMetadata($file_path, $read_from_cache),
            );

            $this->_cacheSet($cache_key, $data);

            $data['from-cache'] = false;
            return $data;
        }
        
        /**
         * Returns the files duration as a Timecode object if available otherwise returns false.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns a Timecode object if the duration is found, otherwise returns null.
         */
        public function getFileDuration($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_duration';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          grab the duration
            $data = null;
            if(preg_match('/Duration:\s+([^,]*)/', $raw_data, $matches) > 0)
            {
                $data = new Timecode($matches[1], Timecode::INPUT_FORMAT_TIMECODE);
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns the files duration as a Timecode object if available otherwise returns false.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns a Timecode object if the duration is found, otherwise returns null.
         */
        public function getFileGlobalMetadata($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_global_meta';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          grab the duration
            $data = null;
            if(preg_match('/Metadata:(.*)Duration:/ms', $raw_data, $meta_matches) > 0)
            {
                if(preg_match_all('/([a-z\_]+)\s+\:\s+(.*)/', $meta_matches[1], $meta_matches) > 0)
                {
                    foreach ($meta_matches[2] as $key => $value)
                    {
                        $data[$meta_matches[1][$key]] = $value;
                    }
                }
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns the files bitrate if available otherwise returns -1.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns the bitrate as an integer if available otherwise returns -1.
         */
        public function getFileBitrate($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_bitrate';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          grab the bitrate
            $data = null;
            if(preg_match('/bitrate:\s+(N\/A|[0-9\.]+\s?[bkBmg\/s]+)/', $raw_data, $matches) > 0)
            {
                $data = strtoupper($matches[1]) === 'N/A' ? -1 : (int) $matches[1];
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns the start point of the file as a Timecode object if available, otherwise returns null.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns a Timecode object if the start point is found, otherwise returns null.
         */
        public function getFileStart($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_start';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          grab the bitrate
            $data = null;
            if(preg_match('/start:\s+([^,]*)/', $raw_data, $matches) > 0)
            {
                $data = new Timecode($matches[1], Timecode::INPUT_FORMAT_SECONDS);
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns the start point of the file as a Timecode object if available, otherwise returns null.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns a string 'audio' or 'video' if media is audio or video, otherwise returns null.
         */
        public function getFileType($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_type';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);

//          grab the type
            $data = null;
            if(preg_match('/Stream.*:\s+Video:\s+.*/', $raw_data, $matches) > 0)
            {
//              special check to see if the file is actually an image and not a video.
                if(strpos(Mime::get($file_path), 'image/') !== false)
                {
                    $data = 'image';
                }
                elseif(strpos(Mime::get($file_path), 'audio/') !== false)
                {
                    $data = 'audio';
                }
                else
                {
                    $data = 'video';
                }
            }
            else if(preg_match('/Stream.*:\s+Audio:\s+.*/', $raw_data, $matches) > 0)
            {
                $data = 'audio';
            }
            else
            {
                $data = null;
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }

        /**
         * Returns the files mean volume and max volume if available, otherwise returns null.
         *
         * @access public
         * @author Samar Rizvi
         * @param string $file_path
         * @param boolean $read_from_cache
         * @return mixed Returns an array of found data, otherwise returns null.
         */
        public function getFileVolumeComponent($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_volume';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }

//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);

//          grab the volume
            $data = null;

            if(preg_match_all ('/^.*(mean_volume)(:)(\s+)([+-]?\d*\.\d+)(?![-+0-9\.])( )(dB)/im', $raw_data, $matches) > 0)
            {
                $data = array();

                $float1=$matches[4][0];
                $word_unit=$matches[6][0];

                $data['mean_volume'] = $float1 . $word_unit;
            }

            if(preg_match_all ('/^.*(max_volume)(:)(\s+)([+-]?\d*\.\d+)(?![-+0-9\.])( )(dB)/im', $raw_data, $matches) > 0)
            {
                if(!is_array($data)) {
                    $data = array();
                }

                $float1=$matches[4][0];
                $word_unit=$matches[6][0];

                $data['max_volume'] = $float1 . $word_unit;
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns any video information about the file if available.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns an array of found data, otherwise returns null.
         */
        public function getFileVideoComponent($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_video_component';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);

//          match the video stream info
            $data = null;
            if(preg_match('/Stream(.*):\s+Video:\s+(.*)/', $raw_data, $matches) > 0)
            {
                $data = array(
                    'stream'                => null,
                    'dimensions'            =>  array(
                        'width'                 => null,
                        'height'                => null,
                        'aspect_ratio_fix_warning'=> false,
                    ),
                    'bitrate'               => null,
                    'time_bases'            => array(),
                    'frames'                => array(
                        'total'                 => null,
                        'rate'                  => null,
                        'avg_rate'              => null,
                    ),
                    'pixel_aspect_ratio'    => null,
                    'display_aspect_ratio'  => null,
                    'rotation'              => null,
                    'pixel_format'          => null,
                    'language'              => null,
                    'codec'                 => array(
                        'name'                  => null,
                        'profile'               => null,
                        'tag_string'            => null,
                        'tag'                   => null,
                        'raw'                   => null,
                    ),
                    'metadata' => array(),
                );
                
                $other_parts = array();
                
//              get the stream
                if(preg_match('/#([0-9\:]{3,})/', $matches[0], $stream_matches) > 0)
                {
                    $data['stream'] = $stream_matches[1];
                }

//              get the language
                if(preg_match('/\(([a-z\_\-]{2,})\)/', $matches[0], $lang_matches) > 0)
                {
                    $data['language'] = $lang_matches[1];
                }

//              get the ratios
                if(preg_match('/\[[P|S]AR\s+([0-9\:\.]+)\s+DAR\s+([0-9\:\.]+)\]/', $matches[0], $ratio_matches) > 0)
                {
                    $data['pixel_aspect_ratio'] = $ratio_matches[1];
                    $data['display_aspect_ratio'] = $ratio_matches[2];
                }
                else if(preg_match('/[P|S]AR\s+([0-9\:\.]+)\s+DAR\s+([0-9\:\.]+)/', $matches[0], $ratio_matches) > 0)
                {
                    $data['pixel_aspect_ratio'] = $ratio_matches[1];
                    $data['display_aspect_ratio'] = $ratio_matches[2];
                }
                
//              get the dimension parts
                if(preg_match('/([1-9][0-9]*)x([1-9][0-9]*)/', $matches[2], $dimensions_matches) > 0)
                {
//                  this is a special fix for correctly reading width and height from an ffmpeg rotated video.
//                  not entirely sure if this is the same across the board for every video or just ffmpeg rotated videos.
                    $dimensions = $dimensions_matches;
                    if($data['display_aspect_ratio'] !== null)
                    {
                        $pixel_ratio = false;
                        if(preg_match('/^[0-9]+\.[0-9]+$/', $data['display_aspect_ratio'], $_m) > 0)
                        {
                            $pixel_ratio = array(
                                $data['display_aspect_ratio'],
                                1
                            );
                        }
                        else if(preg_match('/^[0-9]+:[0-9]+$/', $data['display_aspect_ratio'], $_m) > 0)
                        {
                            $pixel_ratio = explode(':', $data['display_aspect_ratio'], 2);
                        }
                        if($pixel_ratio !== false)
                        {
                            if($pixel_ratio[0] < $pixel_ratio[1] && $dimensions_matches[1] > $dimensions_matches[2])
                            {
                                $dimensions[1] = $dimensions_matches[2];
                                $dimensions[2] = $dimensions_matches[1];
                                $data['dimensions']['aspect_ratio_fix_warning'] = true;
                            }
                        }
                    }
                    $data['dimensions']['width'] = (float) $dimensions[1];
                    $data['dimensions']['height'] = (float) $dimensions[2];
                }
                $dimension_match = $dimensions_matches[0];
                array_push($other_parts, $dimension_match);

//              get the timebases
                $data['time_bases'] = array();
                if(preg_match_all('/([0-9\.k]+)\s+(fps|tbr|tbc|tbn)/', $matches[0], $timebase_matches) > 0)
                {
                    foreach ($timebase_matches[2] as $key => $abrv)
                    {
                        $data['time_bases'][$abrv] = $timebase_matches[1][$key];
                    }
                }
                $timebase_match = implode(', ', $timebase_matches[0]);
                array_push($other_parts, $timebase_match);
            
//              get the video frames per second
                $fps = isset($data['time_bases']['fps']) === true ? $data['time_bases']['fps'] : 
                      (isset($data['time_bases']['tbr']) === true ? $data['time_bases']['tbr'] : 
                       false);
                if ($fps !== false)
                {
                    $data['frames']['rate'] = (float) $fps;
                    $duration_timecode = $this->getFileDuration($file_path, $read_from_cache);
                    $data['frames']['total'] = $duration_timecode !== null ? ceil($duration_timecode->seconds * $data['frames']['rate']) : null;
                }

//              get the bit rate
                if(preg_match('/([0-9]{1,4})\s+(kb|mb)\/s/i', $matches[0], $bitrate_matches) > 0)
                {
                    $bit_rate = (float) $bitrate_matches[1];
                    $bit_rate_type = strtolower($bitrate_matches[2]);
                    $bit_rate_multiplier = ($bit_rate_type === 'mb' ? 8192000 : 1000);
                    $data['bitrate'] = $bit_rate * $bit_rate_multiplier;
                    array_push($other_parts, $bitrate_matches[0]);
                }

//              formats should be anything left over, let me know if anything else exists
                $parts = explode(',', $matches[2]);
                $formats = array();
                foreach ($parts as $key => $part)
                {
                    $part = trim($part);
                    if(in_array($part, $other_parts) === false)
                    {
                        array_push($formats, $part);
                    }
                }
                $data['pixel_format'] = $formats[1];
                
//              get the codec details
                $data['codec']['name'] = 
                $data['codec']['raw'] = isset($formats[0]) === true ? $formats[0] : null;
                if(preg_match('/([^\s]+)(\s*.*)?/', $data['codec']['raw'], $codec_matches) > 0)
                {
                    $data['codec']['name'] = $codec_matches[1];
                    if(isset($codec_matches[2]) === true)
                    {
                        if(preg_match('/\(([^\/\)]+)\)/', $codec_matches[2], $codec_sub_matches) > 0)
                        {
                            $data['codec']['profile'] = $codec_sub_matches[1];
                        }
                        if(preg_match('/\(([^\s]+)\s\/\s([^\s\)]+)\)/', $codec_matches[2], $codec_sub_matches) > 0)
                        {
                            $data['codec']['tag'] = $codec_sub_matches[1];
                            $data['codec']['tag_string'] = $codec_sub_matches[2];
                        }
                    }
                }

//              get metadata from the video input, (if any)
                $meta_data_search_from = strpos($raw_data, $matches[0]);
                $meta_data_search = trim(substr($raw_data, $meta_data_search_from+strlen($matches[0])));
                if(strpos($meta_data_search, 'Metadata:') === 0 && preg_match('/Metadata:(.*)Stream/ms', $meta_data_search, $meta_matches) > 0)
                {
                    if(preg_match_all('/([a-z\_]+)\s+\:\s+(.*)/', $meta_matches[1], $meta_matches) > 0)
                    {
                        foreach ($meta_matches[2] as $key => $value)
                        {
                            $data['metadata'][$meta_matches[1][$key]] = $value;
                        }
                    }
                }
                
//              do we have a meta data rotation?
                if(isset($data['metadata']['rotate']) === true)
                {
                    $data['rotation'] = $data['metadata']['rotate'];
                }
            }
            
            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns any audio information about the file if available.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns an array of found data, otherwise returns null.
         */
        public function getFileAudioComponent($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_audio_component';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          match the audio stream info
            $data = null;
            if(preg_match('/Stream(.*):\s+Audio:\s+(.*)/', $raw_data, $matches) > 0)
            {
                $data = array(
                    'stream'        => null,
                    'stereo'        => null,
                    'channels'      => null,
                    'sample'        => array(
                        'format'       => null,
                        'rate'         => null,
                        'bits_per'     => null,
                    ),
                    'bitrate'       => null,
                    'language'      => null,
                    'codec'         => array(
                        'name'         => null,
                        'profile'      => null,
                        'tag_string'   => null,
                        'tag'          => null,
                        'raw'          => null,
                    ),
                    'metadata'      => array(),
                );
                
                $other_parts = array();
                
//              get the stream
                if(preg_match('/#([0-9\:]{3,})/', $matches[0], $stream_matches) > 0)
                {
                    $data['stream'] = $stream_matches[1];
                }

//              get the language
                if(preg_match('/\(([a-z\_\-]{2,})\)/', $matches[0], $lang_matches) > 0)
                {
                    $data['language'] = $lang_matches[1];
                }

//              get the stereo value
                if(preg_match('/(stereo|mono|5.1)/i', $matches[0], $stereo_matches) > 0)
                {
                    $data['stereo'] = $stereo_matches[0];
                    $data['channels'] = $stereo_matches[0] === 'mono' ? 1 : ($stereo_matches[0] === 'stereo' ? 2 : ($stereo_matches[0] === '5.1' ? 6 : 0));
                    array_push($other_parts, $stereo_matches[0]);
                }
                
//              get the sample_rate
                if(preg_match('/([0-9]{3,6})\s+Hz/', $matches[0], $sample_matches) > 0)
                {
                    $data['sample']['rate'] = (float) $sample_matches[1];
                    array_push($other_parts, $sample_matches[0]);
                }

//              get the bit rate
                if(preg_match('/([0-9]{1,4})\s+(kb|mb)\/s/i', $matches[0], $bitrate_matches) > 0)
                {
                    $bit_rate = (float) $bitrate_matches[1];
                    $bit_rate_type = strtolower($bitrate_matches[2]);
                    $bit_rate_multiplier = ($bit_rate_type === 'mb' ? 8192000 : 1000);
                    $data['bitrate'] = $bit_rate * $bit_rate_multiplier;
                    array_push($other_parts, $bitrate_matches[0]);
                }

//              formats should be anything left over, let me know if anything else exists
                $parts = explode(',', $matches[2]);
                $formats = array();
                foreach ($parts as $key => $part)
                {
                    $part = trim($part);
                    if(in_array($part, $other_parts) === false)
                    {
                        array_push($formats, $part);
                    }
                }
//              get the codec details
                $data['codec']['name'] = 
                $data['codec']['raw'] = isset($formats[0]) === true ? $formats[0] : null;
                if(preg_match('/([^\s]+)(\s*.*)?/', $data['codec']['raw'], $codec_matches) > 0)
                {
                    $data['codec']['name'] = $codec_matches[1];
                    if(isset($codec_matches[2]) === true)
                    {
                        if(preg_match('/\(([^\/\)]+)\)/', $codec_matches[2], $codec_sub_matches) > 0)
                        {
                            $data['codec']['profile'] = $codec_sub_matches[1];
                        }
                        if(preg_match('/\(([^\s]+)\s\/\s([^\s\)]+)\)/', $codec_matches[2], $codec_sub_matches) > 0)
                        {
                            $data['codec']['tag'] = $codec_sub_matches[1];
                            $data['codec']['tag_string'] = $codec_sub_matches[2];
                        }
                    }
                }
                
//              get metadata from the audio input, (if any)
//              however if we have a video source in the media it is outputted differently than just pure audio.
                $meta_data_search_from = strpos($raw_data, $matches[0]);
                $meta_data_search = trim(substr($raw_data, $meta_data_search_from+strlen($matches[0])));
                if(strpos($meta_data_search, 'Metadata:') === 0 && preg_match('/Metadata:(.*)(?:Stream|At\sleast)/ms', $meta_data_search, $meta_matches) > 0)
                {
                    if(preg_match('/Metadata:(.*)(?:Stream|At\sleast)/ms', $meta_data_search, $meta_matches) > 0)
                    {
                        if(preg_match_all('/([a-z\_]+)\s+\:\s+(.*)/', $meta_matches[1], $meta_matches) > 0)
                        {
                            foreach ($meta_matches[2] as $key => $value)
                            {
                                $data['metadata'][$meta_matches[1][$key]] = $value;
                            }
                        }
                    }
//                  this is just pure audio and is essnetially id3 data.
                    else if(preg_match('/Metadata:(.*)(?:Duration)/ms', $raw_data, $meta_matches) > 0)
                    {
                        if(preg_match_all('/([a-z\_]+)\s+\:\s+(.*)/', $meta_matches[1], $meta_matches) > 0)
                        {
                            foreach ($meta_matches[2] as $key => $value)
                            {
                                $data['metadata'][$meta_matches[1][$key]] = $value;
                            }
                        }
                    }
                }
            }
            
            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns a boolean value determined by the media having an audio channel.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return boolean
         */
        public function getFileHasAudio($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_has_audio';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key)))
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          match the audio stream info
            $data = false;
            if(preg_match('/Stream.+Audio/', $raw_data, $matches) > 0)
            {
                $data = true;
            }
            
            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns a boolean value determined by the media having a video channel.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return boolean
         */
        public function getFileHasVideo($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_parsed_has_video';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key)))
            {
                return $data;
            }
            
//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);
            
//          match the audio stream info
            $data = false;
            if(preg_match('/Stream.+Video/', $raw_data, $matches) > 0)
            {
                $data = true;
            }
            
            $this->_cacheSet($cache_key, $data);
            return $data;
        }
        
        /**
         * Returns the raw data provided by ffmpeg about a file.
         *
         * @access public
         * @author Oliver Lillie
         * @param string $file_path 
         * @param boolean $read_from_cache 
         * @return mixed Returns false if no data is returned, otherwise returns the raw data as a string.
         */
        public function getFileRawInformation($file_path, $read_from_cache=true)
        {
//          convert to realpath
            $real_file_path = $this->_checkMediaFilePath($file_path);

            $cache_key = 'media_parser/'.md5($real_file_path).'_raw_data';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key, -1)) !== -1)
            {
                return $data;
            }

            $isWindowsPlatform = defined('PHP_WINDOWS_VERSION_BUILD');

//          execute the ffmpeg lookup
            $exec = new FfmpegProcess('ffmpeg', $this->_config);
            $exec_cmd = $exec->setInputPath($real_file_path)
                             ->addCommand('-af', 'volumedetect')
                             ->addCommand('-f', 'NULL');

            if($isWindowsPlatform) {
                $exec_cmd = $exec_cmd->addCommand('NUL');
            } else {
                $exec_cmd = $exec_cmd->addCommand('/dev/null');
            }

            $raw_data = $exec_cmd->execute()
                                 ->getBuffer();
            
//          check the process for any errors.
            if($exec->hasError() === true && ($last_line = $exec->getLastLine()) !== 'At least one output file must be specified')
            {
                throw new FfmpegProcessException('FFmpeg encountered an error when attempting to read `'.$file_path.'`. FFmpeg reported: 
'.$raw_data, null, $exec);
            }

//          check that some data has been obtained
            if(empty($raw_data) === true)
            {
                // TODO possible error/exception here.
            }
            
            $this->_cacheSet($cache_key, $raw_data);
            return $raw_data;
        }

        /**
         * Returns the container-format.
         *
         * @author Andreas Heigl
         * @param string $file_path
         * @param boolean $read_from_cache
         *
         * @return string
         */
        public function getFileContainerFormat($file_path, $read_from_cache=true)
        {
            $cache_key = 'media_parser/'.md5(realpath($file_path)).'_container_format';
            if($read_from_cache === true && ($data = $this->_cacheGet($cache_key)))
            {
                return $data;
            }

//          get the raw data
            $raw_data = $this->getFileRawInformation($file_path, $read_from_cache);

//          match the audio stream info
            $data = '';
            if(preg_match('/Input\s#0,\s+([^\s]+),\s+from/', $raw_data, $matches) > 0)
            {
                $data = $matches[1];
            }

            $this->_cacheSet($cache_key, $data);
            return $data;
        }
    }
