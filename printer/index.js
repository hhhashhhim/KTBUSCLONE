const express = require('express')
const app = express()
const port = 3000

app.get('/', (req, res) => {
    const ThermalPrinter = require("node-thermal-printer").printer;
    const PrinterTypes = require("node-thermal-printer").types;

    let printer = new ThermalPrinter({
        type: PrinterTypes.STAR,                                  // Printer type: 'star' or 'epson'
        interface: 'tcp://xxx.xxx.xxx.xxx',                       // Printer interface
        characterSet: 'SLOVENIA',                                 // Printer character set - default: SLOVENIA
        removeSpecialCharacters: false,                           // Removes special characters - default: false
        lineCharacter: "=",                                       // Set character for lines - default: "-"
        options:{                                                 // Additional options
          timeout: 5000                                           // Connection timeout (ms) [applicable only for network printers] - default: 3000
        }
      });

      console.log(printer);
})

app.listen(port, () => console.log(`Example app listening at http://localhost:${port}`))
