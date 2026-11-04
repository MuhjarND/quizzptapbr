<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class QuizRequest extends FormRequest
{
    public function authorize() { return (bool) $this->user(); }
    public function rules()
    {
        return ['title' => 'required|string|max:120', 'description' => 'nullable|string|max:2000',
            'status' => 'required|in:draft,ready', 'theme' => 'required|in:islands,science,tech',
            'cover' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'questions' => 'required|array|min:1|max:50', 'questions.*.text' => 'required|string|max:1000',
            'questions.*.duration' => 'required|integer|min:5|max:120',
            'questions.*.image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'questions.*.existing_id' => 'nullable|integer',
            'questions.*.choices' => 'required|array|min:2|max:4', 'questions.*.choices.*' => 'required|string|max:300',
            'questions.*.correct_index' => 'required|integer|min:0|max:3'];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('questions', []) as $i => $q) {
                if (is_array($q) && !array_key_exists($q['correct_index'] ?? -1, (array) ($q['choices'] ?? [])))
                    $validator->errors()->add('questions.'.$i.'.correct_index', 'Pilih tepat satu jawaban benar dari pilihan yang tersedia.');
            }
        });
    }
    public function messages() { return ['required' => ':attribute wajib diisi.', 'max' => ':attribute melebihi batas maksimum (:max).', 'min' => ':attribute minimal :min.', 'image' => 'Unggah gambar JPG, PNG, atau WebP.', 'mimes' => 'Format gambar harus JPG, PNG, atau WebP.']; }
    public function attributes() { return ['title' => 'Judul kuis', 'description'=>'Deskripsi', 'status'=>'Status kuis', 'theme'=>'Ilustrasi sampul', 'questions'=>'Daftar pertanyaan', 'questions.*.text' => 'Teks pertanyaan', 'questions.*.choices'=>'Pilihan jawaban', 'questions.*.choices.*' => 'Pilihan jawaban', 'questions.*.duration'=>'Waktu menjawab', 'questions.*.correct_index'=>'Jawaban benar', 'questions.*.image'=>'Gambar pertanyaan', 'cover' => 'Gambar sampul']; }
}
