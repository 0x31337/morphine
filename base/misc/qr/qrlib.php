<?php
/*
 * PHP QR Code encoder
 *
 * Root library file, prepares environment and application dependencies
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
	
	$QR_BASEDIR = dirname(__FILE__).DIRECTORY_SEPARATOR;
	
	// Required libs
	
	include_once $QR_BASEDIR . "qrconst.php";
	include_once $QR_BASEDIR . "qrconfig.php";
	include_once $QR_BASEDIR . "qrtools.php";
	include_once $QR_BASEDIR . "qrspec.php";
	include_once $QR_BASEDIR . "qrimage.php";
	include_once $QR_BASEDIR . "qrinput.php";
	include_once $QR_BASEDIR . "qrbitstream.php";
	include_once $QR_BASEDIR . "qrsplit.php";
	include_once $QR_BASEDIR . "qrrscode.php";
	include_once $QR_BASEDIR . "qrmask.php";
	include_once $QR_BASEDIR . "qrencode.php";

