window.addEventListener('beforeunload', function() {
    // Aqui você pode limpar dados do sessionStorage, localStorage 
    // ou disparar alguma ação que zere dados do front.
    // Por exemplo, se você estiver armazenando dados no sessionStorage:
    sessionStorage.clear();
});
