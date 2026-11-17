
import os
import time
import serial
import requests

# PAS DIT AAN
COM_PORT = "COM3"
BAUDRATE = 9600

API_URL = "http://localhost/boerbert-2026/api.php"
SCANNER_KEY = os.environ["BERT_SCANNER_KEY"]

FACILITY = "douche"

def send_scan(uid):
    payload = {
        "f": FACILITY,
        "card": uid
    }

    try:
        response = requests.post(
            API_URL,
            json=payload,
            headers={"X-Key": SCANNER_KEY},
            timeout=5
        )

        data = response.json()

        if response.ok and data.get("ok"):
            action = data.get("action")
            occupancy = data.get("occupancy")

            print(
                f"Scan: {uid} | "
                f"Actie: {action} | "
                f"Bezetting: {occupancy}"
            )

            if action == "in":
                return "ALLOW_IN"
            elif action == "out":
                return "ALLOW_OUT"
            elif action == "ignored":
                return "IGNORED"

        print("Geweigerd:", data.get("error"))
        return "DENIED"

    except (requests.RequestException, ValueError) as e:
        print("Verbindingsfout:", e)
        return "ERROR"


def main():
    print("De Groene Weide - RFID Bridge")
    print("Verbinden met", COM_PORT)

    with serial.Serial(
        COM_PORT,
        BAUDRATE,
        timeout=1
    ) as arduino:

        time.sleep(2)
        arduino.reset_input_buffer()

        print("Scanner verbonden!")

        while True:
            line = arduino.readline().decode(
                "utf-8", errors="replace"
            ).strip()

            if not line.startswith("SCAN:"):
                continue

            uid = line[5:].strip()

            if not uid:
                continue

            result = send_scan(uid)

            arduino.write(
                (result + "\n").encode("utf-8")
            )
            arduino.flush()


if __name__ == "__main__":
    try:
        main()
    except serial.SerialException as e:
        print("Seriële poortfout:", e)
    except KeyboardInterrupt:
        print("\nScanner gestopt.")
