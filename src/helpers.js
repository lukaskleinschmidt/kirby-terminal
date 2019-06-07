export function parseANSI(string) {

  // Class name mapping
  const values = {
    1: 'term-bold',
    2: 'term-faint',
    3: 'term-italic',
    4: 'term-underline',
    8: 'term-conceal',
    9: 'term-cross',
    30: 'term-fg-black',
    31: 'term-fg-red',
    32: 'term-fg-green',
    33: 'term-fg-yellow',
    34: 'term-fg-blue',
    35: 'term-fg-magenta',
    36: 'term-fg-cyan',
    37: 'term-fg-white',
    90: 'term-fg-bright-black',
    91: 'term-fg-bright-red',
    92: 'term-fg-bright-green',
    93: 'term-fg-bright-yellow',
    94: 'term-fg-bright-blue',
    95: 'term-fg-bright-magenta',
    96: 'term-fg-bright-cyan',
    97: 'term-fg-bright-white',
    40: 'term-bg-black',
    41: 'term-bg-red',
    42: 'term-bg-green',
    43: 'term-bg-yellow',
    44: 'term-bg-blue',
    45: 'term-bg-magenta',
    46: 'term-bg-cyan',
    47: 'term-bg-white',
    100: 'term-bg-bright-black',
    101: 'term-bg-bright-red',
    102: 'term-bg-bright-green',
    103: 'term-bg-bright-yellow',
    104: 'term-bg-bright-blue',
    105: 'term-bg-bright-magenta',
    106: 'term-bg-bright-cyan',
    107: 'term-bg-bright-white'
  };

  // Reset patterns
  const patterns = {
    0: /[0-9]+/,
    21: /^1$/,
    22: /^(1|2)$/,
    23: /^3$/,
    24: /^4$/,
    25: /^(5|6)$/,
    28: /^8$/,
    29: /^9$/,
    39: /^(3|9)[0-7]$/,
    49: /^(4|10)[0-7]$/,
    54: /^(51|52)$/,
    55: /^53$/,
    65: /^6[0-4]$/,
  };

  // The current style context
  let context = [];

  // Whether there is a stray open span tag
  let stray = false;

  // Remove everything from the context matching the reset pattern of
  // the passed code
  function reset(code) {
    if (code in patterns == false) return;
    context = context.filter(item => {
      return patterns[code].test(item.code) === false;
    });
  }

  // Parse the string
  string = string.replace(/\033\[([0-9;]*)([A-Za-z])/g, (match, codes, type) => {

    // Only handle sequences that modify the appearance
    if (type !== 'm') return '';

    codes.split(';').forEach(code => {
      const value = values[code.trim()] || null;

      if (value) {
        // Because fore and background colors must be unique we reset
        // any previously set fore or background colors
        [39, 49].some(key => {

          // Test if the code matches a fore or background color
          if (patterns[key].test(code)) {
            reset(key);
            return true;
          }
        });

        // Add the value to the current context
        return context.push({
          value: value,
          code: code
        });
      }

      // When the value is not defined the code either resets the
      // context with a specific pattern or the sequence is simply
      // removed from the string
      return reset(code);
    });

    let result = '';

    if (stray) {
      stray = false;
      result +='</span>';
    }

    if (context.length) {
      stray = true;
      result += `<span class="${context.map(item => item.value).join(' ')}">`;
    }

    return result;
  });

  // Close any stray open span
  if (stray) string += '</span>';

  return string;
}
