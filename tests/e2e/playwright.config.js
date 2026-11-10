// Playwright config for the consent E2E suite (run on the AWS test rig).
module.exports = {
	testDir: __dirname,
	testIgnore: [ 'live/**' ],
	timeout: 30000,
	retries: 0,
	workers: 1,
	reporter: [ [ 'list' ] ],
	use: { ignoreHTTPSErrors: true, httpCredentials: process.env.BFC_HTTP_USER ? { username: process.env.BFC_HTTP_USER, password: process.env.BFC_HTTP_PASS } : undefined, trace: 'retain-on-failure' },
};
