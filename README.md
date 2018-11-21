# kindle-display-munin :bar_chart:
Display Munin Graphs on Amazon Kindle

## Intro

For more information see Matthew Petroff's original idea at https://mpetroff.net/2012/09/kindle-weather-display/

## Setup

I use four Mijia Bluetooth temp/hum sensors in my setup. Munin generates values every 5 minutes, so I generate a new image every 4:

```
*/4 * * * * /home/pigpen/kindle/generate_kindle.sh > /dev/null 2>&1
```

## Example
![Kindle Example](https://github.com/mreymann/kindle-display-munin/blob/master/example.png)
The graphs show the changes over the last 3 hours. The gaps originate from crappy Bluetooth reception. :-(
