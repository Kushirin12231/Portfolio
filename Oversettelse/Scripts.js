document.addEventListener("DOMContentLoaded", () => {
  // Azure Translator API Key and Endpoint
  const apiKey = "1BIpmvSCs4wd73Oy6TjDePBRFXPbAaXvgItH5NNF4q4LwBkrxrlJJQQJ99AKAChHRaEXJ3w3AAAbACOGpTqR"; // Replace with your key
  const endpoint = "https://api.cognitive.microsofttranslator.com/translate?api-version=3.0";

  let typingTimer; // Timer to delay translation
  const typingDelay = 500; // 500ms delay after user stops typing

  const translateText = async () => {
    const inputText = document.getElementById("input-text").value;
    const fromLang = document.getElementById("language-from").value;
    const toLang = document.getElementById("language-to").value;

    if (!inputText.trim()) {
      document.getElementById("output-text").value = ""; // Clear output if input is empty
      return;
    }

    try {
      // Fetch translation from API
      const response = await fetch(`${endpoint}&from=${fromLang}&to=${toLang}`, {
        method: "POST",
        headers: {
          "Ocp-Apim-Subscription-Key": apiKey,
          "Content-Type": "application/json",
          "Ocp-Apim-Subscription-Region": "norwayeast", // Replace with your Azure region
        },
        body: JSON.stringify([{ Text: inputText }]),
      });

      if (!response.ok) {
        throw new Error("Translation failed. Check your API key or input data.");
      }

      const data = await response.json();
      const translatedText = data[0].translations[0].text;

      // Update the output textarea
      document.getElementById("output-text").value = translatedText;
    } catch (error) {
      console.error("Error during translation:", error);
    }
  };

  // Add input event listener with a typing delay
  document.getElementById("input-text").addEventListener("input", () => {
    clearTimeout(typingTimer); // Reset timer on every keystroke
    typingTimer = setTimeout(translateText, typingDelay); // Trigger translation after delay
  });

  // Translate immediately on language dropdown change
  document.getElementById("language-from").addEventListener("change", translateText);
  document.getElementById("language-to").addEventListener("change", translateText);
});
