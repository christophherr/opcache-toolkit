/**
 * Logger Initialization Entry Point.
 *
 * This file handles the global initialization of the Logger service
 * when loaded as a standalone script.
 *
 * @package
 */

import { Logger } from '../services/Logger';

const endpoint = window.opcacheToolkitData?.restUrl + 'opcache-toolkit/v1/log';
const nonce = window.opcacheToolkitData?.nonce;

if ( endpoint && nonce ) {
	const logger = new Logger( endpoint, nonce );
	logger.registerGlobalHandlers();
	window.opcacheToolkitLogger = logger;
}
