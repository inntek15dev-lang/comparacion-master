<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Contratista;
use Illuminate\Support\Collection;

class NotificacionDocumentos extends Mailable
{
    use Queueable, SerializesModels;

    public Contratista $contratista;
    public Collection $documentosVencidos;
    public Collection $documentosRechazados;
    public string $nombreMandante;
    public string $mensajePersonalizado; // <-- NUEVA PROPIEDAD

    /**
     * Create a new message instance.
     */
    public function __construct(Contratista $contratista, Collection $documentosVencidos, Collection $documentosRechazados, string $nombreMandante, string $mensajePersonalizado) // <-- NUEVO PARÁMETRO
    {
        $this->contratista = $contratista;
        $this->documentosVencidos = $documentosVencidos;
        $this->documentosRechazados = $documentosRechazados;
        $this->nombreMandante = $nombreMandante;
        $this->mensajePersonalizado = $mensajePersonalizado; // <-- ASIGNACIÓN
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $asunto = 'Aviso: Documentos con Observaciones';
        if ($this->documentosVencidos->isNotEmpty() && $this->documentosRechazados->isNotEmpty()) {
            $asunto = 'Aviso: Documentos Vencidos y Rechazados';
        } elseif ($this->documentosVencidos->isNotEmpty()) {
            $asunto = 'Aviso: Documentos Vencidos';
        } elseif ($this->documentosRechazados->isNotEmpty()) {
            $asunto = 'Aviso: Documentos Rechazados';
        }

        return new Envelope(
            subject: $asunto . ' - ' . $this->nombreMandante,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-documentos',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}