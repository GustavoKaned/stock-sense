#include <WiFi.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "SEU_WIFI";
const char* WIFI_SENHA = "SUA_SENHA";
const char* API_URL = "http://SEU_SITE/api/leitura.php";
const char* TOKEN = "COLOQUE_O_TOKEN_DO_DISPOSITIVO";

const int TRIG_PIN = 5;
const int ECHO_PIN = 18;

// Ajuste conforme a sua maquete.
const float DISTANCIA_VAZIA_CM = 30.0;
const float DISTANCIA_CHEIA_CM = 5.0;

float lerDistanciaCm() {
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);

    long duracao = pulseIn(ECHO_PIN, HIGH, 30000);

    if (duracao <= 0) {
        return -1;
    }

    return duracao / 58.0;
}

float calcularPercentual(float distancia) {
    float percentual =
        (DISTANCIA_VAZIA_CM - distancia) * 100.0 /
        (DISTANCIA_VAZIA_CM - DISTANCIA_CHEIA_CM);

    return constrain(percentual, 0.0, 100.0);
}

void enviarLeitura(float distancia, float percentual) {
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }

    HTTPClient http;
    http.begin(API_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", TOKEN);

    String json = "{\"distancia_cm\":" + String(distancia, 2) +
                  ",\"percentual_ocupado\":" + String(percentual, 2) + "}";

    int codigo = http.POST(json);

    Serial.print("HTTP: ");
    Serial.println(codigo);
    Serial.println(http.getString());

    http.end();
}

void setup() {
    Serial.begin(115200);

    pinMode(TRIG_PIN, OUTPUT);
    pinMode(ECHO_PIN, INPUT);

    WiFi.begin(WIFI_SSID, WIFI_SENHA);

    Serial.print("Conectando ao Wi-Fi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.println("Wi-Fi conectado.");
}

void loop() {
    float distancia = lerDistanciaCm();

    if (distancia >= 0) {
        float percentual = calcularPercentual(distancia);

        Serial.print("Distancia: ");
        Serial.print(distancia, 2);
        Serial.print(" cm | Ocupacao: ");
        Serial.print(percentual, 2);
        Serial.println("%");

        enviarLeitura(distancia, percentual);
    } else {
        Serial.println("Falha na leitura do HC-SR04.");
    }

    delay(5000);
}
