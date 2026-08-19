<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewords every experience option so it does not begin "My child".
 *
 * A family with three children had to read each statement as though it were
 * about one of them. The selection is about the household, so the wording is
 * now subject-free — "Finding the school environment overwhelming" rather than
 * "My child finds the school environment overwhelming" — and uses
 * them/their/themselves wherever a person is needed, which reads the same for
 * one child or four.
 *
 * Slugs are untouched, so nobody who already chose one of these is affected.
 *
 * Applied only where a label is still the original. Anything already reworded
 * in the admin panel is left exactly as the editor wrote it.
 */
return new class extends Migration
{
    /** slug => [was, now] */
    private const LABELS = [
        'school-attendance' => ['My child struggles with attending school or feels anxious about school', 'Struggling to get to school, or anxiety about school'],
        'school-environment' => ['My child finds the school environment overwhelming', 'Finding the school environment overwhelming'],
        'learning-concentration' => ['My child struggles with learning, concentration or independent work', 'Struggling with learning, concentration or working independently'],
        'additional-support' => ['My child needs additional support or adjustments in education', 'Needing extra support or adjustments at school'],
        'homework-exams' => ['My child finds homework, exams or assessments difficult', 'Finding homework, exams or assessments difficult'],
        'school-friendships' => ['My child finds friendships, social situations or belonging at school difficult', 'Finding friendships or belonging at school difficult'],
        'education-options' => ['We are exploring different education options (specialist, alternative provision or home education)', 'Exploring other education options — specialist, alternative provision or home education'],
        'school-communication' => ['We are finding communication with school or education providers challenging', 'Finding communication with school difficult'],
        'anxiety' => ['My child experiences anxiety or worries that affect daily life', 'Anxiety or worries that affect daily life'],
        'low-mood' => ['My child struggles with emotional wellbeing or low mood', 'Low mood, or struggling with emotional wellbeing'],
        'sleep-eating' => ['My child has difficulties with sleep, eating or mealtimes', 'Difficulties with sleep, eating or mealtimes'],
        'personal-care' => ['My child needs support with personal care, toileting or hygiene', 'Needing support with personal care, toileting or hygiene'],
        'ongoing-health-needs' => ['My child has ongoing health needs, medical appointments or treatments', 'Ongoing health needs, appointments or treatments'],
        'pain-fatigue' => ['My child experiences pain, fatigue or reduced energy', 'Pain, fatigue or reduced energy'],
        'awaiting-health-support' => ['We are waiting for health assessments or support', 'Waiting for health assessments or support'],
        'carer-overwhelm' => ['I feel overwhelmed by caring responsibilities or supporting my child’s wellbeing', 'Feeling overwhelmed by caring, or by supporting their wellbeing'],
        'speech-language' => ['My child finds communication, speech or language difficult', 'Finding communication, speech or language difficult'],
        'communicating-needs' => ['My child struggles to communicate their needs or feelings', 'Struggling to communicate needs or feelings'],
        'social-communication' => ['My child finds social communication or understanding others difficult', 'Finding social communication or understanding others difficult'],
        'everyday-skills' => ['My child needs support developing everyday skills and independence', 'Needing support with everyday skills and independence'],
        'organisation-routines' => ['My child finds organisation, instructions or routines difficult', 'Finding organisation, instructions or routines difficult'],
        'learns-differently' => ['My child learns differently from their peers or needs extra time to develop skills', 'Learning differently from their peers, or needing extra time'],
        'sensory-differences' => ['My child experiences sensory differences or becomes overwhelmed by their environment', 'Sensory differences, or being overwhelmed by the environment'],
        'understanding-development' => ['We are learning more about our child’s strengths, needs or development', 'Learning more about their strengths, needs or development'],
        'regulating-emotions' => ['My child becomes overwhelmed easily or struggles to regulate emotions', 'Becoming overwhelmed easily, or struggling to regulate emotions'],
        'emotional-outbursts' => ['My child experiences emotional outbursts or big feelings', 'Emotional outbursts, or big feelings'],
        'change-transitions' => ['My child finds changes, transitions or uncertainty difficult', 'Finding change, transitions or uncertainty difficult'],
        'behaviour-that-challenges' => ['We are trying to understand and support behaviours that challenge us', 'Trying to understand and support behaviour that challenges us'],
        'family-routines-affected' => ['Our family routines or activities are affected by our child’s needs', 'Family routines or activities shaped around their needs'],
        'social-relationships' => ['My child finds social situations, friendships or relationships difficult', 'Finding social situations, friendships or relationships difficult'],
        'supporting-siblings' => ['We are supporting siblings or other family members through challenges', 'Supporting siblings or other family members through challenges'],
        'feeling-judged' => ['Our family sometimes feels judged or misunderstood by others', 'Feeling judged or misunderstood by other people'],
        'awaiting-assessment' => ['We are waiting for assessments or exploring possible additional needs', 'Waiting for assessments, or exploring possible additional needs'],
        'ehcp' => ['We are applying for or managing an EHCP', 'Applying for or managing an EHCP'],
        'understanding-send' => ['We are trying to understand SEND services and available support', 'Trying to understand SEND services and what support exists'],
        'working-with-professionals' => ['We are working with professionals (e.g. therapists, health or education teams)', 'Working with professionals — therapists, health or education teams'],
        'local-groups' => ['We are looking for local activities, groups or community support', 'Looking for local activities, groups or community support'],
        'practical-financial-respite' => ['We are looking for practical, financial or respite support', 'Looking for practical, financial or respite support'],
        'systems-paperwork' => ['We find systems, paperwork or waiting lists difficult to navigate', 'Finding systems, paperwork or waiting lists hard to navigate'],
        'advice-from-parents' => ['We would value advice from parents with similar experiences', 'Wanting advice from parents with similar experiences'],
        'isolated' => ['I feel isolated and would like to connect with parents who understand', 'Feeling isolated, and wanting to meet parents who understand'],
        'overwhelmed' => ['I feel overwhelmed by caring responsibilities', 'Feeling overwhelmed by caring responsibilities'],
        'balancing-work' => ['I am balancing caring responsibilities with work or other commitments', 'Balancing caring with work or other commitments'],
        'learning-to-advocate' => ['I am learning how to advocate for my child', 'Learning how to advocate for them'],
        'right-decisions' => ['I worry about making the right decisions for my child', 'Worrying about making the right decisions'],
        'adapting-routines' => ['Our family is adapting routines and expectations around our child’s needs', 'Adapting routines and expectations around their needs'],
        'wider-family-pressures' => ['We are supporting siblings or managing wider family pressures', 'Supporting siblings, or managing wider family pressures'],
        'not-alone' => ['I would like reassurance that we are not alone', 'Wanting reassurance that we are not alone'],
        'starting-a-setting' => ['My child is starting nursery, school or a new setting', 'Starting nursery, school or a new setting'],
        'moving-schools' => ['My child is moving between schools or education stages', 'Moving between schools or education stages'],
        'puberty-teenager' => ['My child is approaching puberty or becoming a teenager', 'Approaching puberty, or becoming a teenager'],
        'greater-independence' => ['My child is preparing for greater independence', 'Preparing for greater independence'],
        'adulthood-employment' => ['We are thinking about adulthood, employment or future opportunities', 'Thinking about adulthood, employment or the future'],
        'adult-services' => ['We are moving between children’s and adult services', 'Moving between children’s and adult services'],
        'planning-future-support' => ['We are planning future support for our child', 'Planning future support'],
        'worried-about-future' => ['I feel uncertain or worried about what the future holds', 'Feeling uncertain or worried about the future'],
        'confidence' => ['My child struggles with confidence or self-esteem', 'Struggling with confidence or self-esteem'],
        'feels-different' => ['My child feels different from their peers', 'Feeling different from their peers'],
        'fitting-in' => ['My child finds fitting in or making connections difficult', 'Finding it hard to fit in or make connections'],
        'masking' => ['My child hides parts of themselves to fit in (masking)', 'Hiding parts of themselves to fit in (masking)'],
        'understanding-themselves' => ['We are helping our child understand themselves', 'Helping them understand themselves'],
        'accepted-valued' => ['We want our child to feel accepted and valued', 'Wanting them to feel accepted and valued'],
        'celebrating-strengths' => ['We want to celebrate our child’s strengths and individuality', 'Celebrating their strengths and individuality'],
    ];

    /** category slug => [was, now] */
    private const DESCRIPTIONS = [
        'child-development' => ['Understanding your child’s development and independence', 'Understanding their development and independence'],
    ];

    public function up(): void
    {
        $this->apply(0, 1);
    }

    public function down(): void
    {
        $this->apply(1, 0);
    }

    private function apply(int $from, int $to): void
    {
        foreach (self::LABELS as $slug => $text) {
            DB::table('form_options')
                ->where('slug', $slug)
                ->where('label', $text[$from])
                ->update(['label' => $text[$to]]);
        }

        foreach (self::DESCRIPTIONS as $slug => $text) {
            DB::table('form_options')
                ->where('slug', $slug)
                ->whereNull('parent_id')
                ->where('description', $text[$from])
                ->update(['description' => $text[$to]]);
        }
    }
};
