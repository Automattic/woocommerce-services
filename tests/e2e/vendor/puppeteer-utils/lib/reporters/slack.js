"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.sendFailedTestMessageToSlack = sendFailedTestMessageToSlack;
exports.sendFailedTestScreenshotToSlack = sendFailedTestScreenshotToSlack;
exports.sendMessageToSlack = sendMessageToSlack;
exports.sendSnippetToSlack = sendSnippetToSlack;
var _fs = require("fs");
var _webApi = require("@slack/web-api");
var _config = _interopRequireDefault(require("config"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
/**
 * External dependencies
 */

const {
  TRAVIS_BRANCH,
  TRAVIS_REPO_SLUG,
  TRAVIS_PULL_REQUEST_BRANCH,
  TRAVIS_BUILD_WEB_URL,
  E2E_SLACK_TOKEN,
  E2E_CHANNEL_NAME,
  TRAVIS_PULL_REQUEST,
  E2E_CC_USERS,
  E2E_SLACKBOT_USER,
  E2E_SLACKBOT_EMOJI
} = process.env;
const token = E2E_SLACK_TOKEN ? E2E_SLACK_TOKEN : _config.default.has('slackToken') ? _config.default.get('slackToken') : '';
const conversationId = E2E_CHANNEL_NAME ? E2E_CHANNEL_NAME : _config.default.has('slackChannel') ? _config.default.get('slackChannel') : '';
const ccUserList = E2E_CC_USERS ? E2E_CC_USERS : _config.default.has('ccUsers') ? _config.default.get('ccUsers') : '';
const slackBotUsername = E2E_SLACKBOT_USER ? E2E_SLACKBOT_USER : _config.default.has('slackBotUsername') ? _config.default.get('slackBotUsername') : '';
const slackBotEmoji = E2E_SLACKBOT_EMOJI ? E2E_SLACKBOT_EMOJI : _config.default.has('slackBotEmoji') ? _config.default.get('slackBotEmoji') : '';
const webCli = new _webApi.WebClient(token);
const repoURL = `https://github.com/${TRAVIS_REPO_SLUG}`;
const branchName = TRAVIS_PULL_REQUEST_BRANCH !== '' ? TRAVIS_PULL_REQUEST_BRANCH : TRAVIS_BRANCH;
let ccUsers;
if (ccUserList != '') {
  ccUsers = 'cc ' + ccUserList;
}
async function sendRequestToSlack(fn) {
  try {
    await fn();
  } catch (error) {
    // Check the code property and log the response
    if (error.code === _webApi.ErrorCode.PlatformError || error.code === _webApi.ErrorCode.RequestError || error.code === _webApi.ErrorCode.RateLimitedError || error.code === _webApi.ErrorCode.HTTPError) {
      console.log(error.data);
    } else {
      // Some other error, oh no!
      console.log('The error occurred does not match an error we are checking for in this block.');
      console.log(error);
    }
  }
}
const createSection = (text, type = 'mrkdwn') => {
  return {
    type: 'section',
    text: {
      type,
      text
    }
  };
};
const getMessage = ({
  name,
  block,
  error
}) => {
  let testFailure = '';
  if (error.name || error.message) {
    testFailure = error.name + ': ' + error.message;
  }
  const testFullName = block + ' :: ' + name;
  const message = [];
  message.push(createSection(`*TEST FAILED:* ${testFullName}
*Failure reason:* ${testFailure}
*Travis build:* ${TRAVIS_BUILD_WEB_URL}
*Github branch:* ${branchName}
*Github PR URL:* ${repoURL}/pull/${TRAVIS_PULL_REQUEST}`));
  if (ccUsers) {
    message.push(createSection(ccUsers));
  }
  return message;
};
async function sendFailedTestMessageToSlack(testResult) {
  await sendMessageToSlack(getMessage(testResult));
}
async function sendMessageToSlack(message) {
  const payload = {
    channel: conversationId,
    username: slackBotUsername,
    icon_emoji: slackBotEmoji
  };
  if (typeof message === 'string') {
    payload.text = message;
  } else {
    payload.blocks = message;
  }

  // For details, see: https://api.slack.com/methods/chat.postMessage
  await sendRequestToSlack(async () => await webCli.chat.postMessage(payload));
}
async function sendSnippetToSlack(message) {
  const payload = {
    channels: conversationId,
    username: slackBotUsername,
    icon_emoji: slackBotEmoji,
    content: message
  };
  return await sendRequestToSlack(async () => await webCli.files.upload(payload));
}
async function sendFailedTestScreenshotToSlack(screenshotOfFailedTest) {
  const payload = {
    filename: screenshotOfFailedTest,
    file: (0, _fs.createReadStream)(screenshotOfFailedTest),
    channels: conversationId
  };

  // For details, see: https://api.slack.com/methods/files.upload
  return await sendRequestToSlack(async () => await webCli.files.upload(payload));
}