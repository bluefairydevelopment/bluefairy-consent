// Playwright config for the consent E2E suite (run on the AWS test rig).
module.exports = {
	testDir: __dirname,
	timeout: 30000,
	retries: 0,
	workers: 1,
	reporter: [ [ 'list' ] ],
	use: { ignoreHTTPSErrors: true, trace: 'retain-on-failure' },
};
