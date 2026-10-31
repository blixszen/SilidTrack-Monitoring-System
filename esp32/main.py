# ============================================================
# SilidTrack — ESP32 Attendance Scanner (MicroPython)
# Hardware: ESP32, RFID RC522 (SPI), Active Buzzer, I2C SSD1306 OLED 128x64
# ============================================================
#
# Wiring:
#   RC522 (SPI)     -> ESP32
#   SDA/SS          -> GPIO 5
#   SCK             -> GPIO 18
#   MOSI            -> GPIO 19
#   MISO            -> GPIO 23
#   RST             -> GPIO 4
#   3.3V / GND
#
#   SSD1306 OLED (I2C) -> ESP32
#   SDA             -> GPIO 21
#   SCL             -> GPIO 22
#   VCC             -> 3V3
#   GND             -> GND
#
#   Active Buzzer   -> GPIO 25 (SIGNAL), GND -> GND
#
# Install the `mfrc522` and `ssd1306` MicroPython drivers on the device first.
# ssd1306 driver: https://github.com/stlehmann/micropython-ssd1306
# ============================================================

import network
import urequests
import ujson
import utime
from machine import Pin, SoftSPI, I2C
from mfrc522 import MFRC522
from ssd1306 import SSD1306_I2C

# -------------------- CONFIG --------------------
WIFI_SSID = "WHEYAN"
WIFI_PASSWORD = "blixdre864817"

API_URL = "http://YOUR_SERVER_IP/silidtrack/api/attendance_scan.php"
HEARTBEAT_URL = "http://YOUR_SERVER_IP/silidtrack/api/device_heartbeat.php"
DEVICE_KEY = "silidtrack-esp32-key-change-me"  # must match api/attendance_scan.php

BUZZER_PIN = 25
SCAN_COOLDOWN_MS = 3000  # avoid duplicate rapid scans of the same card
HEARTBEAT_INTERVAL_MS = 30000  # send heartbeat every 30 seconds

OLED_WIDTH = 128
OLED_HEIGHT = 64

# -------------------- HARDWARE SETUP --------------------
buzzer = Pin(BUZZER_PIN, Pin.OUT)
buzzer.value(0)

# SPI for RC522: SCK=18, MOSI=19, MISO=23
spi = SoftSPI(baudrate=100000, polarity=0, phase=0,
              sck=Pin(18), mosi=Pin(19), miso=Pin(23))
sda_rfid = Pin(5, Pin.OUT)
rst_rfid = Pin(4, Pin.OUT)
reader = MFRC522(spi, sda_rfid, rst_rfid)

# I2C for SSD1306 OLED: SDA=21, SCL=22
i2c = I2C(0, scl=Pin(22), sda=Pin(21), freq=400000)
oled = SSD1306_I2C(OLED_WIDTH, OLED_HEIGHT, i2c)

last_uid = None
last_scan_time = 0
last_heartbeat_time = 0


def beep(pattern="success"):
    """success = one short beep, error = two short beeps"""
    if pattern == "success":
        buzzer.value(1)
        utime.sleep_ms(120)
        buzzer.value(0)
    else:
        for _ in range(2):
            buzzer.value(1)
            utime.sleep_ms(80)
            buzzer.value(0)
            utime.sleep_ms(80)


def oled_clear():
    """Clear the OLED display"""
    oled.fill(0)
    oled.show()


def oled_print(line1, line2="", line3="", line4="", center=True):
    """
    Print up to 4 lines on the 128x64 OLED.
    Each line is ~16px high, so 4 lines fit comfortably.
    """
    oled.fill(0)
    lines = [line1, line2, line3, line4]
    y = 0
    for i, line in enumerate(lines):
        if line:
            if center:
                # Approximate centering: 128px wide, 8px per char
                x = max(0, (OLED_WIDTH - len(line) * 8) // 2)
            else:
                x = 0
            oled.text(line, x, y)
        y += 16
    oled.show()


def connect_wifi():
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    if not wlan.isconnected():
        oled_print("Connecting", "WiFi...")
        wlan.connect(WIFI_SSID, WIFI_PASSWORD)
        timeout = 20
        while not wlan.isconnected() and timeout > 0:
            utime.sleep(1)
            timeout -= 1
    if wlan.isconnected():
        ip = wlan.ifconfig()[0]
        oled_print("WiFi Connected", ip)
    else:
        oled_print("WiFi Failed!", "Check Credentials")
    return wlan.isconnected()


def uid_to_hex(uid_bytes):
    return "".join("{:02X}".format(b) for b in uid_bytes)


def send_scan(uid_hex):
    payload = ujson.dumps({"uid": uid_hex, "device_key": DEVICE_KEY})
    try:
        response = urequests.post(
            API_URL,
            data=payload,
            headers={"Content-Type": "application/json"}
        )
        result = response.json()
        response.close()
        return result
    except Exception as e:
        print("Request failed:", e)
        return None


def send_heartbeat():
    """Send periodic heartbeat to server to indicate device is online"""
    payload = ujson.dumps({"device_key": DEVICE_KEY})
    try:
        response = urequests.post(
            HEARTBEAT_URL,
            data=payload,
            headers={"Content-Type": "application/json"}
        )
        result = response.json()
        response.close()
        return result
    except Exception as e:
        print("Heartbeat failed:", e)
        return None


def handle_scan(uid_hex):
    oled_print("Scanning...", uid_hex)
    result = send_scan(uid_hex)

    if result is None:
        beep("error")
        oled_print("Connection", "Error", "Try Again")
        return

    if result.get("ok"):
        status = result.get("status", "Present")
        name = result.get("student_name", "Student")
        beep("success")
        # Truncate name if too long for display
        display_name = name[:16]
        oled_print("Logged!", "{}".format(display_name), "Status: {}".format(status))
    else:
        beep("error")
        error_msg = result.get("error", "Unknown card")
        oled_print("Scan Failed", error_msg[:16])


def show_ready():
    oled_print("SilidTrack", "Ready", "Tap your card")


def main():
    # Initial display
    oled_print("SilidTrack", "Starting...")
    utime.sleep_ms(500)

    if connect_wifi():
        show_ready()
        print("SilidTrack ESP32 scanner ready.")
    else:
        print("WiFi connection failed. Retrying in background...")

    global last_uid, last_scan_time, last_heartbeat_time

    while True:
        now = utime.ticks_ms()

        # Send periodic heartbeat
        if utime.ticks_diff(now, last_heartbeat_time) > HEARTBEAT_INTERVAL_MS:
            last_heartbeat_time = now
            send_heartbeat()

        reader.init()
        (stat, tag_type) = reader.request(reader.REQIDL)

        if stat == reader.OK:
            (stat, raw_uid) = reader.anticoll()
            if stat == reader.OK:
                uid_hex = uid_to_hex(raw_uid)

                # Debounce: ignore the same card re-triggering within cooldown window
                if uid_hex != last_uid or utime.ticks_diff(now, last_scan_time) > SCAN_COOLDOWN_MS:
                    last_uid = uid_hex
                    last_scan_time = now
                    handle_scan(uid_hex)
                    utime.sleep_ms(500)

        utime.sleep_ms(150)


if __name__ == "__main__":
    main()