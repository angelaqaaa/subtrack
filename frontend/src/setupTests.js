// jest-dom adds custom jest matchers for asserting on DOM nodes.
// allows you to do things like:
// expect(element).toHaveTextContent(/react/i)
// learn more: https://github.com/testing-library/jest-dom
import '@testing-library/jest-dom';
import { TextEncoder, TextDecoder } from 'util';

// react-router 7 and jspdf need TextEncoder when they load, jspdf (through
// fast-png) also needs TextDecoder, and the jsdom in react-scripts 5 has neither
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;
